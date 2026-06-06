import express from "express";
import path from "path";
import fs from "fs";
import { type Request, type Response } from "express";
import { GoogleGenAI } from "@google/genai";
import { createServer as createViteServer } from "vite";

const app = express();
const PORT = 3000;
const DB_FILE = path.join(process.cwd(), "school_db.json");

app.use(express.json());

// Initialize Gemini API Client
const geminiApiKey = process.env.GEMINI_API_KEY || "";
let aiClient: GoogleGenAI | null = null;

if (geminiApiKey && geminiApiKey !== "MY_GEMINI_API_KEY") {
  aiClient = new GoogleGenAI({
    apiKey: geminiApiKey,
    httpOptions: {
      headers: {
        "User-Agent": "aistudio-build",
      },
    },
  });
}

// Global In-Memory / File-based Database Simulating MySQL
interface User {
  id: string;
  username: string;
  role: string;
  name: string;
}

interface News {
  id: string;
  title: string;
  category: string;
  content: string;
  summary?: string;
  imageUrl: string;
  date: string;
  views: number;
}

interface Teacher {
  id: string;
  name: string;
  position: string;
  level: string; // e.g. ผู้อำนวยการ, ครูชำนาญการพิเศษ, ครูประจำชั้น, บุคลากรทางการศึกษา
  subjectGroup?: string;
  imageUrl: string;
  order: number;
}

interface Student {
  id: string;
  name: string;
  grade: string; // e.g. อนุบาล 2, ป.1, ป.6
  classroom: string; // e.g. 1/1
  gender: string;
}

interface DownloadDoc {
  id: string;
  title: string;
  category: string; // e.g. แผนการเรียน, จัดซื้อจัดจ้าง, ใบสมัครเรียน, เอกสารทั่วไป
  fileType: string; // PDF, WORD, etc.
  fileSize: string;
  downloadCount: number;
  date: string;
}

interface GalleryAlbum {
  id: string;
  title: string;
  description: string;
  coverImage: string;
  images: string[];
  date: string;
}

interface Banner {
  id: string;
  title: string;
  subtitle: string;
  imageUrl: string;
  active: boolean;
}

interface SchoolSettings {
  schoolName: string;
  shortName: string;
  address: string;
  phone: string;
  email: string;
  jurisdiction: string;
  levels: string;
  directorName: string;
  directorTitle: string;
  directorImage: string;
  visitorCount: number;
  youtubeIntroUrl: string;
}

interface DatabaseSchema {
  users: User[];
  news: News[];
  teachers: Teacher[];
  students: Student[];
  downloads: DownloadDoc[];
  galleries: GalleryAlbum[];
  banners: Banner[];
  settings: SchoolSettings;
}

const defaultDatabase: DatabaseSchema = {
  users: [
    { id: "1", username: "admin", role: "Administrator", name: "ผู้ดูแลระบบ โรงเรียนบ้านหนองหว้า" }
  ],
  banners: [
    {
      id: "1",
      title: "ยินดีต้อนรับสู่ โรงเรียนบ้านหนองหว้า",
      subtitle: "แหล่งวิทยาการ กีฬาเด่น เน้นคุณธรรม สัมพันธ์ชุมชน",
      imageUrl: "https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&q=80&w=1200",
      active: true
    },
    {
      id: "2",
      title: "เปิดรับสมัครเรียน ปีการศึกษา 2569",
      subtitle: "ตั้งแต่ชั้น อนุบาล 1 ถึง ชั้นประถมศึกษาปีที่ 6",
      imageUrl: "https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&q=80&w=1200",
      active: true
    }
  ],
  settings: {
    schoolName: "โรงเรียนบ้านหนองหว้า",
    shortName: "ร.ร.บ้านหนองหว้า",
    address: "หมู่ที่ 2 บ้านหนองหว้า ตำบลหนองกี่ อำเภอหนองกี่ จังหวัดบุรีรัมย์ 31210",
    phone: "044-641123",
    email: "bannongwaschool@gmail.com",
    jurisdiction: "สำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3",
    levels: "ระดับปฐมวัย (อนุบาล 2-3) ถึงระดับชั้นประถมศึกษาปีที่ 6",
    directorName: "นายอำนวย ยอดครูใหญ่",
    directorTitle: "ผู้อำนวยการโรงเรียนบ้านหนองหว้า",
    directorImage: "https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300",
    visitorCount: 15420,
    youtubeIntroUrl: "https://www.youtube.com/embed/gCOk8X63Rpk"
  },
  news: [
    {
      id: "1",
      title: "ประกาศเปิดเรียนภาคเรียนที่ 1 ปีการศึกษา 2569 อย่างเป็นทางการ",
      category: "ประชาสัมพันธ์ทั่วไป",
      content: "โรงเรียนบ้านหนองหว้า ขอประกาศกำหนดการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 ขอความกรุณาผู้ปกครองเตรียมความพร้อมของนักเรียนในเรื่องของเครื่องแบบ อุปกรณ์การเรียน และสุขอนามัย ทางโรงเรียนได้ทำความสะอาดฉีดพ่นฆ่าเชื้อและเตรียมอาคารสถานที่เรียบร้อยแล้ว",
      summary: "ประกาศอย่างเป็นทางการเปิดภาคเรียนที่ 1 ปีการศึกษา 2569 ในวันที่ 16 พฤษภาคม 2569 พร้อมทั้งเตรียมความสะอาดของอาคารสถานที่และการดูแลความปลอดภัยในทุกด้าน",
      imageUrl: "https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=600",
      date: "2026-05-10",
      views: 312
    },
    {
      id: "2",
      title: "กิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 'น้อมจิตวันทา บูชาพระคุณครู'",
      category: "ข่าวกิจกรรม",
      content: "โรงเรียนบ้านหนองหว้า นำโดยคณะผู้บริหาร คณะครู และสภานักเรียน ได้ร่วมใจจัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 ณ หอประชุมโรงเรียน เพื่อร่วมส่งเสริมวัฒนธรรมอันดีงามและความกตัญญูกตเวทิตาต่อครูผู้ประสิทธิ์ประสาทวิชา โดยมีการประกวดพานไหว้ครูประเภทสวยงามและประเภทความคิดสร้างสรรค์ บรรยากาศเป็นไปด้วยความอบอุ่นและเป็นระเบียบเรียบร้อย",
      summary: "โรงเรียนบ้านหนองหว้า จัดกิจกรรมวันไหว้ครู ประจำปีการศึกษา 2569 ณ หอประชุมโรงเรียน เพื่อแสดงความกตัญญูกตเวทิตา พร้อมทั้งประกวดพานไหว้ครูอันสวยงามเชิงสร้างสรรค์",
      imageUrl: "https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=600",
      date: "2026-06-02",
      views: 185
    },
    {
      id: "3",
      title: "การประชุมผู้ปกครองภาคทฤษฎีและแนวทางการเรียนร่วม ภาคเรียนที่ 1/2569",
      category: "ประชุมและวิชาการ",
      content: "เมื่อวันเสาร์ที่ผ่านมา ทางโรงเรียนจัดประชุมผู้ปกครองภาคเรียนที่ 1 ปีการศึกษา 2569 เพื่อชี้แจงนโยบายสิทธิประโยชน์เรียนฟรี 15 ปี เผยแพร่มาตรการความปลอดภัย และการร่วมมือกันพัฒนาทักษะอ่านออกเขียนได้ของนักเรียน โดยการประชุมประสบความสำเร็จและได้รับความร่วมมืออย่างดียิ่งจากผู้ปกครองทุกระดับชั้น",
      summary: "จัดประชุมผู้ปกครองภาคเรียนที่ 1/2569 เพื่อสร้างความเข้าใจต่อนโยบายโรงเรียน สิทธิประโยชน์เรียนฟรี และแนวทางประสานงานเพื่อช่วยเหลือดูแลพฤติกรรมการเรียนของนักเรียน",
      imageUrl: "https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&q=80&w=600",
      date: "2026-05-18",
      views: 247
    }
  ],
  teachers: [
    { id: "1", name: "นายอำนวย ยอดครูใหญ่", position: "ผู้อำนวยการโรงเรียนบ้านหนองหว้า", level: "ผู้อำนวยการโรงเรียน (คศ.3)", subjectGroup: "ผู้บริหาร", imageUrl: "https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=300", order: 1 },
    { id: "2", name: "นางสมศรี ปัญญาไว", position: "ครูวิชาการระดับประถม / ครูประจำชั้นประถมศึกษาปีที่ 6", level: "ครูชำนาญการพิเศษ (คศ.3)", subjectGroup: "วิชาการคณิตศาสตร์", imageUrl: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300", order: 2 },
    { id: "3", name: "นายวิชาญ อักษรศิลป์", position: "ครูพลศึกษาและไอที / ครูประจำชั้นประถมศึกษาปีที่ 5", level: "ครูชำนาญการ (คศ.2)", subjectGroup: "สุขศึกษาและพลศึกษา", imageUrl: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=300", order: 3 },
    { id: "4", name: "นางสาวดวงตา บุพผา", position: "ครูภาษาไทยระดับต้น / ครูประจำชั้นประถมศึกษาปีที่ 1", level: "ครูผู้ช่วย", subjectGroup: "ภาษาไทย", imageUrl: "https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&q=80&w=300", order: 4 },
    { id: "5", name: "นางกานดา ใจซื่อ", position: "ครูปฐมวัย / ครูประจำชั้นอนุบาล 3", level: "ครูชำนาญการพิเศษ (คศ.3)", subjectGroup: "ระดับปฐมวัย", imageUrl: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=300", order: 5 }
  ],
  students: [
    { id: "1", name: "เด็กชายจิรายุ สมพงษ์", grade: "ประถมศึกษาปีที่ 6", classroom: "6/1", gender: "ชาย" },
    { id: "2", name: "เด็กหญิงรัตนาภรณ์ แสนดี", grade: "ประถมศึกษาปีที่ 6", classroom: "6/1", gender: "หญิง" },
    { id: "3", name: "เด็กชายวีรยุทธ สุขใจ", grade: "ประถมศึกษาปีที่ 5", classroom: "5/1", gender: "ชาย" },
    { id: "4", name: "เด็กหญิงกนกวรรณ เพียรธรรม", grade: "ประถมศึกษาปีที่ 1", classroom: "1/1", gender: "หญิง" },
    { id: "5", name: "เด็กชายอานนท์ บุรีรัมย์", grade: "อนุบาล 3", classroom: "อ.3/1", gender: "ชาย" }
  ],
  downloads: [
    { id: "1", title: "ใบสมัครเข้าศึกษาต่อ ระดับชั้นอนุบาลและประถมศึกษา โรงเรียนบ้านหนองหว้า", category: "เอกสารทั่วไป", fileType: "PDF", fileSize: "1.2 MB", downloadCount: 145, date: "2026-03-01" },
    { id: "2", title: "แผนพัฒนาการศึกษา 5 ปี (พ.ศ. 2568 - 2572) โรงเรียนบ้านหนองหว้า", category: "แผนงานและนโยบาย", fileType: "PDF", fileSize: "4.5 MB", downloadCount: 56, date: "2026-02-15" },
    { id: "3", title: "รายงานการประเมินตนเองของสถานศึกษา SAR ปีการศึกษา 2568", category: "ประกันคุณภาพ", fileType: "PDF", fileSize: "8.1 MB", downloadCount: 92, date: "2026-04-10" },
    { id: "4", title: "ข้อตกลงในการพัฒนางาน PA สำหรับครูสายการสอน (ตัวอย่างไฟล์แก้ไขได้)", category: "เอกสารครู", fileType: "WORD", fileSize: "520 KB", downloadCount: 231, date: "2026-05-02" }
  ],
  galleries: [
    {
      id: "1",
      title: "บรรยากาศการจัดบูธ นิทรรศการและประเมินผลสัมฤทธิ์โรงเรียนพระราชทาน",
      description: "ทางโรงเรียนจัดกิจกรรมเผยแพร่ผลความสำเร็จและงานฝีมือของสภานักเรียนในวันประเมินสถานศึกษา",
      coverImage: "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&q=80&w=600",
      images: [
        "https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&q=80&w=600",
        "https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&q=80&w=600",
        "https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&q=80&w=600"
      ],
      date: "2026-03-24"
    },
    {
      id: "2",
      title: "การจัดแข่งขันกีฬาภายใน 'หนองหว้าสปอร์ตเกมส์' สานรักสุขภาพ",
      description: "ส่งเสริมการทำงานเป็นทีมและค่านิยมสุขภาพที่แข็งแรงของเด็กอนุบาลและนักเรียนประถม",
      coverImage: "https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=600",
      images: [
        "https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&q=80&w=600",
        "https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&q=80&w=600"
      ],
      date: "2026-01-18"
    }
  ]
};

// Database Read/Write Utilities
function loadDb(): DatabaseSchema {
  try {
    if (fs.existsSync(DB_FILE)) {
      const dataStr = fs.readFileSync(DB_FILE, "utf-8");
      return JSON.parse(dataStr);
    }
  } catch (error) {
    console.error("Error reading database file, using fallback.", error);
  }
  return defaultDatabase;
}

function saveDb(data: DatabaseSchema) {
  try {
    fs.writeFileSync(DB_FILE, JSON.stringify(data, null, 2), "utf-8");
  } catch (error) {
    console.error("Error writing database file.", error);
  }
}

// Ensure database is initialized with current visitor tracking
let db = loadDb();
if (!fs.existsSync(DB_FILE)) {
  saveDb(defaultDatabase);
}

// Increment visual visits counts
db.settings.visitorCount += 1;
saveDb(db);

/* 
=========================================
          DATABASE CRUD API ROUTES
=========================================
*/

// GET all db records
app.get("/api/db", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb);
});

// GET School settings
app.get("/api/db/settings", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.settings);
});

// POST update school settings
app.post("/api/db/settings", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.settings = { ...currentDb.settings, ...req.body };
  saveDb(currentDb);
  res.json({ success: true, settings: currentDb.settings });
});

// BANNERS API
app.get("/api/db/banners", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.banners);
});

app.post("/api/db/banners", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newBanner = { ...req.body, id: Date.now().toString() };
  currentDb.banners.push(newBanner);
  saveDb(currentDb);
  res.json({ success: true, banner: newBanner });
});

app.put("/api/db/banners/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const bannerIndex = currentDb.banners.findIndex(b => b.id === req.params.id);
  if (bannerIndex !== -1) {
    currentDb.banners[bannerIndex] = { ...currentDb.banners[bannerIndex], ...req.body };
    saveDb(currentDb);
    res.json({ success: true, banner: currentDb.banners[bannerIndex] });
  } else {
    res.status(404).json({ error: "Banner not found" });
  }
});

app.delete("/api/db/banners/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.banners = currentDb.banners.filter(b => b.id !== req.params.id);
  saveDb(currentDb);
  res.json({ success: true });
});

// NEWS CRUD API
app.get("/api/db/news", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.news);
});

app.post("/api/db/news", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const { title, category, content, summary, imageUrl, date } = req.body;
  const newNews: News = {
    id: Date.now().toString(),
    title: title || "ข่าวประกาศประชาสัมพันธ์",
    category: category || "ทั่วไป",
    content: content || "",
    summary: summary || content?.substring(0, 100) || "",
    imageUrl: imageUrl || "https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&q=80&w=600",
    date: date || new Date().toISOString().split("T")[0],
    views: 0
  };
  currentDb.news.push(newNews);
  saveDb(currentDb);
  res.json({ success: true, news: newNews });
});

app.put("/api/db/news/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newsIndex = currentDb.news.findIndex(n => n.id === req.params.id);
  if (newsIndex !== -1) {
    currentDb.news[newsIndex] = { ...currentDb.news[newsIndex], ...req.body };
    saveDb(currentDb);
    res.json({ success: true, news: currentDb.news[newsIndex] });
  } else {
    res.status(404).json({ error: "News not found" });
  }
});

app.delete("/api/db/news/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.news = currentDb.news.filter(n => n.id !== req.params.id);
  saveDb(currentDb);
  res.json({ success: true });
});

app.post("/api/db/news/view/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newsIndex = currentDb.news.findIndex(n => n.id === req.params.id);
  if (newsIndex !== -1) {
    currentDb.news[newsIndex].views += 1;
    saveDb(currentDb);
    res.json({ success: true, views: currentDb.news[newsIndex].views });
  } else {
    res.status(404).json({ error: "News not found" });
  }
});

// TEACHER API
app.get("/api/db/teachers", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.teachers);
});

app.post("/api/db/teachers", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newTeacher: Teacher = {
    ...req.body,
    id: Date.now().toString(),
    order: currentDb.teachers.length + 1
  };
  currentDb.teachers.push(newTeacher);
  saveDb(currentDb);
  res.json({ success: true, teacher: newTeacher });
});

app.put("/api/db/teachers/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const index = currentDb.teachers.findIndex(t => t.id === req.params.id);
  if (index !== -1) {
    currentDb.teachers[index] = { ...currentDb.teachers[index], ...req.body };
    saveDb(currentDb);
    res.json({ success: true, teacher: currentDb.teachers[index] });
  } else {
    res.status(404).json({ error: "Teacher not found" });
  }
});

app.delete("/api/db/teachers/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.teachers = currentDb.teachers.filter(t => t.id !== req.params.id);
  saveDb(currentDb);
  res.json({ success: true });
});

// STUDENT API
app.get("/api/db/students", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.students);
});

app.post("/api/db/students", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newStudent: Student = {
    ...req.body,
    id: Date.now().toString()
  };
  currentDb.students.push(newStudent);
  saveDb(currentDb);
  res.json({ success: true, student: newStudent });
});

app.put("/api/db/students/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const index = currentDb.students.findIndex(s => s.id === req.params.id);
  if (index !== -1) {
    currentDb.students[index] = { ...currentDb.students[index], ...req.body };
    saveDb(currentDb);
    res.json({ success: true, student: currentDb.students[index] });
  } else {
    res.status(404).json({ error: "Student not found" });
  }
});

app.delete("/api/db/students/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.students = currentDb.students.filter(s => s.id !== req.params.id);
  saveDb(currentDb);
  res.json({ success: true });
});

// DOWNLOADS API
app.get("/api/db/downloads", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.downloads);
});

app.post("/api/db/downloads", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newDoc: DownloadDoc = {
    ...req.body,
    id: Date.now().toString(),
    downloadCount: 0,
    date: new Date().toISOString().split("T")[0]
  };
  currentDb.downloads.push(newDoc);
  saveDb(currentDb);
  res.json({ success: true, doc: newDoc });
});

app.put("/api/db/downloads/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const index = currentDb.downloads.findIndex(d => d.id === req.params.id);
  if (index !== -1) {
    currentDb.downloads[index] = { ...currentDb.downloads[index], ...req.body };
    saveDb(currentDb);
    res.json({ success: true, doc: currentDb.downloads[index] });
  } else {
    res.status(404).json({ error: "Document not found" });
  }
});

app.delete("/api/db/downloads/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.downloads = currentDb.downloads.filter(d => d.id !== req.params.id);
  saveDb(currentDb);
  res.json({ success: true });
});

app.post("/api/db/downloads/increment/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const docIndex = currentDb.downloads.findIndex(d => d.id === req.params.id);
  if (docIndex !== -1) {
    currentDb.downloads[docIndex].downloadCount += 1;
    saveDb(currentDb);
    res.json({ success: true, count: currentDb.downloads[docIndex].downloadCount });
  } else {
    res.status(404).json({ error: "Document not found" });
  }
});

// GALLERY ALBUMS API
app.get("/api/db/galleries", (req: Request, res: Response) => {
  const currentDb = loadDb();
  res.json(currentDb.galleries);
});

app.post("/api/db/galleries", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const newGallery: GalleryAlbum = {
    ...req.body,
    id: Date.now().toString(),
    date: new Date().toISOString().split("T")[0],
    images: req.body.images || [req.body.coverImage]
  };
  currentDb.galleries.push(newGallery);
  saveDb(currentDb);
  res.json({ success: true, gallery: newGallery });
});

app.put("/api/db/galleries/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  const index = currentDb.galleries.findIndex(g => g.id === req.params.id);
  if (index !== -1) {
    currentDb.galleries[index] = { ...currentDb.galleries[index], ...req.body };
    saveDb(currentDb);
    res.json({ success: true, gallery: currentDb.galleries[index] });
  } else {
    res.status(404).json({ error: "Gallery not found" });
  }
});

app.delete("/api/db/galleries/:id", (req: Request, res: Response) => {
  const currentDb = loadDb();
  currentDb.galleries = currentDb.galleries.filter(g => g.id !== req.params.id);
  saveDb(currentDb);
  res.json({ success: true });
});

/* 
=========================================
          AI SERVICES (GEMINI API)
=========================================
*/

// Mock / Fallback responses when GEMINI_API_KEY is not configured
const FALLBACK_CHAT_RESPONSES = [
  "ยินดีต้อนรับเข้าสู่โรงเรียนบ้านหนองหว้าครับ มีอะไรให้แอดมินช่วยค้นหาข้อมูลหรือต้องการสอบถามเบอร์โทรผู้บริหารดีครับ",
  "โรงเรียนบ้านหนองหว้า อำเภอหนองกี่ จังหวัดบุรีรัมย์ เปิดสอนตั้งแต่ชั้นอนุบาล 2 ถึง ชั้นประถมศึกษาปีที่ 6 แวะมาเยี่ยมชมโรงเรียนได้ครับ",
  "สำหรับแบบฟอร์มการสมัครเรียน สามารถทำภารกิจดาวน์โหลดได้ที่หน้า 'ดาวน์โหลดเอกสาร' ของสถานศึกษาได้เลยครับผม",
  "ผู้อำนวยการโรงเรียนปัจจุบันคือ นายอำนวย ยอดครูใหญ่ สำหรับช่องทางติดต่อเบอร์หลักคือ 044-641123 ครับ",
  "ยินดีช่วยเหลือครับ คุณครูและแอดมินทุกคนยินดีต้อนรับครับ"
];

// 1. AI Chatbot endpoint
app.post("/api/ai/chatbot", async (req: Request, res: Response) => {
  const { message, history } = req.body;
  if (!message) {
    res.status(400).json({ error: "Message is required" });
    return;
  }

  const currentDb = loadDb();
  const settings = currentDb.settings;
  const recentNewsList = currentDb.news.slice(0, 3).map(n => `- ${n.title} (${n.date})`).join("\n");
  const teachersList = currentDb.teachers.map(t => `- ${t.name} ตำแหน่ง ${t.position}`).join("\n");

  const systemInstruction = `
  คุณคือ 'น้องหว้า AI' แอดมินและผู้ช่วยอัจฉริยะวิเคราะห์ข้อมูลโรงเรียนบ้านหนองหว้า อำเภอหนองกี่ จังหวัดบุรีรัมย์ สังกัดสำนักงานเขตพื้นที่การศึกษาประถมศึกษาบุรีรัมย์ เขต 3 เปิดสอนตั้งแต่ชั้นอนุบาล จนถึง ชั้นประถมศึกษาปีที่ 6
  จงตอบคำถามผู้ใช้งานด้วยความสุภาพ มีไมตรี มีหางเสียงเสมอ (ครับ/ค่ะ) และให้คำแนะนำแบบมืออาชีพ
  
  ข้อมูลอ้างอิงอย่างเป็นทางการของโรงเรียน:
  - ชื่อโรงเรียน: ${settings.schoolName} (${settings.shortName})
  - สังกัด: ${settings.jurisdiction}
  - ที่ตั้ง: ${settings.address}
  - ระดับที่เปิดสอน: ${settings.levels}
  - ผู้อำนวยการโรงเรียน: ${settings.directorName} (${settings.directorTitle})
  - เบอร์โทรศัพท์: ${settings.phone}
  - อีเมล: ${settings.email}
  - โทนสีประจำโรงเรียน: แดง และ เหลือง
  
  ข้อมูลข่าวสารล่าสุดในระบบ:
  ${recentNewsList}
  
  รายชื่อคณะครูและบุคลากร:
  ${teachersList}
  
  คำสั่งเพิ่มเติม:
  - หากถามข้อมูลที่ไม่เกี่ยวกับการศึกษาหรือนอกเหนือขอบเขตโรงเรียนบ้านหนองหว้า ให้ตอบอย่างสุภาพพากลับมาเรื่องโรงเรียนเสมอ
  - ห้ามอ้างอิงข้อมูลภายนอกที่เป็นไปไม่ได้ ให้ใช้ข้อมูลข้างต้นในการตอบเป็นหลัก
  - พยายามสรุปคำตอบให้สั้น กระชับ มีฟอร์แมตให้อ่านง่าย
  `;

  try {
    if (!aiClient) {
      // Return safe responsive in-memory fallback if key is not active
      const randomMsg = FALLBACK_CHAT_RESPONSES[Math.floor(Math.random() * FALLBACK_CHAT_RESPONSES.length)];
      res.json({
        text: `*(โหมดแนะนำออฟไลน์ - ไม่พบรหัสผ่าน Gemini API)* ${randomMsg}`,
        simulated: true,
      });
      return;
    }

    const contents = [];
    if (history && Array.isArray(history)) {
      for (const h of history) {
        contents.push({
          role: h.role === "user" ? "user" : "model",
          parts: [{ text: h.text }],
        });
      }
    }
    contents.push({
      role: "user",
      parts: [{ text: message }],
    });

    const response = await aiClient.models.generateContent({
      model: "gemini-3.5-flash",
      contents,
      config: {
        systemInstruction,
        temperature: 0.7,
      },
    });

    res.json({ text: response.text });
  } catch (error: any) {
    console.error("Gemini AI API Error:", error);
    res.json({
      text: "ขออภัยครับ ระบบประมวลผลเครือข่ายของ น้องหว้า AI มีความล่าช้าในขณะนี้ ข้อความอิงข้อมูลระบบ: โรงเรียนเปิดสอนช่วงอนุบาล-ป.6 ตั้งอยู่ ณ ต.หนองกี่ อ.หนองกี่ จ.บุรีรัมย์ ยินดีบริการครับ!",
      error: error?.message || "Internal error",
    });
  }
});

// 2. AI Summary of News
app.post("/api/ai/summarize", async (req: Request, res: Response) => {
  const { content } = req.body;
  if (!content) {
    res.status(400).json({ error: "Content is required to summarize" });
    return;
  }

  try {
    if (!aiClient) {
      res.json({
        summary: content.substring(0, 120) + "... (สรุปผลแบบสรุปย่อด่วน)",
      });
      return;
    }

    const response = await aiClient.models.generateContent({
      model: "gemini-3.5-flash",
      contents: `จงเขียนสรุปข่าวโรงเรียนตามข้อความด้านล่างนี้ โดยเขียนเป็นภาษาไทยที่เป็นทางการ สั้นกระชับ ความยาวไม่เกิน 2 ประโยค สำหรับนำไปแสดงเป็นแกลเลอรีข่าวด่วน:\n\n${content}`,
    });

    res.json({ summary: response.text?.trim() });
  } catch (err) {
    res.json({ summary: content.substring(0, 120) + "..." });
  }
});

// 3. AI Helper Announcement Creator
app.post("/api/ai/generate-announcement", async (req: Request, res: Response) => {
  const { title, category, keyPoints } = req.body;
  
  const prompt = `เขียนร่างประกาศประเภทราชการภาษาไทยที่เป็นทางการ หัวข้อเรื่องคือ "${title}" ของสถานศึกษา "โรงเรียนบ้านหนองหว้า อำเภอหนองกี่ จังหวัดบุรีรัมย์" 
  หมวดหมู่: ${category}
  ข้อมูลสำคัญที่ต้องระบุในรายละเอียด:
  ${keyPoints}
  
  จงจัดรูปแบบประกาศให้ดูสวยงาม มีขึ้นต้น 'ประกาศโรงเรียนบ้านหนองหว้า เรื่อง... ' รวมทั้งระบุเนื้อความ และลงท้ายให้ลงชื่อผู้อำนวยการ 'นายอำนวย ยอดครูใหญ่' และวันที่ตามจริง`;

  try {
    if (!aiClient) {
      res.json({
        text: `ประกาศโรงเรียนบ้านหนองหว้า\nเรื่อง ${title}\n\nเนื่องด้วยฝ่าย${category} โรงเรียนบ้านหนองหว้า มีความประสงค์แจ้งว่า: ${keyPoints} ทั้งนี้ขอเชิญครูและบุคลากรร่วมรับทราบและปฏิบัติตามอย่างเคร่งครัด\n\nประกาศ ณ วันที่ ${new Date().toLocaleDateString("th-TH")}\n\n(ลงชื่อ) นายอำนวย ยอดครูใหญ่\nผู้อำนวยการโรงเรียนบ้านหนองหว้า`
      });
      return;
    }

    const response = await aiClient.models.generateContent({
      model: "gemini-3.5-flash",
      contents: prompt,
      config: {
        temperature: 0.8,
      }
    });

    res.json({ text: response.text });
  } catch (err: any) {
    res.json({
      text: `เกิดข้อขัดข้องในการเชื่อมต่อกับปัญญาประดิษฐ์จำลอง:\n\nประกาศโรงเรียนบ้านหนองหว้า\nเรื่อง ${title}\n\n${keyPoints}`
    });
  }
});

// 4. AI Document searching helper
app.post("/api/ai/doc-search", (req: Request, res: Response) => {
  const { query } = req.body;
  if (!query) {
    res.json({ suggestions: [] });
    return;
  }

  const currentDb = loadDb();
  const docs = currentDb.downloads;
  
  // Search documents based on keyword matching
  const q = query.toLowerCase();
  const suggestions = docs.filter(
    d => d.title.toLowerCase().includes(q) || d.category.toLowerCase().includes(q)
  );

  res.json({
    suggestions,
    aiRecommendation: suggestions.length > 0
      ? `น้องหว้า AI แนะนำลิ้งค์เอกสาร "${suggestions[0].title}" ซึ่งตรงกับคำสืบค้นของคุณที่สุด สามารถกดดาวน์โหลดได้ทันที`
      : `ขออภัยครับ น้องหว้า AI ค้นหาด้วยคีย์เวิร์ด "${query}" ไม่พบเอกสารในห้องสมุดดิจิทัลโดยตรง แต่อาจเกี่ยวกับ "ใบสมัครเข้าเรียน" หรือ "เอกสารการสอน PA" ที่สามารถดูรายชื่อทังหมดในตารางได้ครับ`
  });
});

/* 
=========================================
          VITE MIDDLEWARE SETUP
=========================================
*/

async function startServer() {
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);

    // Serve transformed index.html for all non-API paths in development
    app.get("*", async (req: Request, res: Response, next) => {
      if (req.path.startsWith("/api/")) {
        return next();
      }
      try {
        const url = req.originalUrl;
        const indexPath = path.join(process.cwd(), "index.html");
        let html = fs.readFileSync(indexPath, "utf-8");
        html = await vite.transformIndexHtml(url, html);
        res.status(200).set({ "Content-Type": "text/html" }).end(html);
      } catch (e: any) {
        vite.ssrFixStacktrace(e);
        next(e);
      }
    });
  } else {
    const distPath = path.join(process.cwd(), "dist");
    app.use(express.static(distPath));
    app.get("*", (req: Request, res: Response) => {
      res.sendFile(path.join(distPath, "index.html"));
    });
  }

  // Start Server Listen
  app.listen(PORT, "0.0.0.0", () => {
    console.log(`[BAN NONG WA] Full-stack Server running at http://localhost:${PORT}`);
  });
}

startServer();
