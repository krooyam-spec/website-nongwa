/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState, useEffect } from "react";
import { 
  Home, 
  Newspaper, 
  Users, 
  GraduationCap, 
  FileDown, 
  Image as ImageIcon, 
  PhoneCall, 
  Settings as SettingsIcon, 
  Bot, 
  Plus, 
  Edit2, 
  Trash2, 
  Search, 
  Sparkles, 
  CheckCircle, 
  BookOpen, 
  Eye, 
  Database,
  Lock,
  Unlock,
  AlertTriangle,
  Download,
  Share2,
  Tv,
  Users2,
  Calendar,
  X,
  FileCode,
  LayoutGrid
} from "lucide-react";
import DeveloperCenter from "./components/DeveloperCenter";
import { 
  type News, 
  type Teacher, 
  type Student, 
  type DownloadDoc, 
  type GalleryAlbum, 
  type Banner, 
  type SchoolSettings, 
  type DatabaseState 
} from "./types";

export default function App() {
  // Navigation active tab: 'home', 'news', 'teachers', 'students', 'downloads', 'galleries', 'contact', 'admin', 'developer'
  const [activeTab, setActiveTab] = useState<string>("home");
  
  // Real-time Database state synchronized with backend
  const [dbState, setDbState] = useState<DatabaseState | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  
  // User Authentication State
  const [isAdminLoggedIn, setIsAdminLoggedIn] = useState<boolean>(false);
  const [loginUsername, setLoginUsername] = useState<string>("");
  const [loginPassword, setLoginPassword] = useState<string>("");
  const [authError, setAuthError] = useState<string>("");

  // AI Chatbot State
  const [chatMessage, setChatMessage] = useState<string>("");
  const [chatHistory, setChatHistory] = useState<Array<{ role: 'user' | 'model', text: string }>>([
    { role: 'model', text: "สวัสดีค่ะ! ยินดีต้อนรับสู่โรงเรียนบ้านหนองหว้า หนูคือ 'น้องหว้า AI' แอดมินดิจิทัลอัจฉริยะ ยินดีให้คำแนะนำแผนการเรียน รายชื่อคุณครู หรือรายละเอียดโครงการต่าง ๆ สอบถามข้อมูลได้เลยค่ะ" }
  ]);
  const [isAiTyping, setIsAiTyping] = useState<boolean>(false);

  // Search & Filter conditions
  const [newsSearch, setNewsSearch] = useState<string>("");
  const [newsCategory, setNewsCategory] = useState<string>("ทั้งหมด");
  
  const [teacherSearch, setTeacherSearch] = useState<string>("");
  const [teacherFilter, setTeacherFilter] = useState<string>("ทั้งหมด");
  
  const [studentSearch, setStudentSearch] = useState<string>("");
  const [studentGrade, setStudentGrade] = useState<string>("ทั้งหมด");
  
  const [docSearch, setDocSearch] = useState<string>("");
  const [docCategory, setDocCategory] = useState<string>("ทั้งหมด");
  const [aiDocLoader, setAiDocLoader] = useState<boolean>(false);
  const [aiDocAdvice, setAiDocAdvice] = useState<string>("");

  // AI Utilities State (Summarizer, Planner)
  const [summaryTargetNewsId, setSummaryTargetNewsId] = useState<string | null>(null);
  const [aiSummaries, setAiSummaries] = useState<Record<string, string>>({});
  const [summarizingId, setSummarizingId] = useState<string | null>(null);

  // AI Instant Announcement State
  const [aiAnnounceTitle, setAiAnnounceTitle] = useState<string>("");
  const [aiAnnounceCategory, setAiAnnounceCategory] = useState<string>("งานวิชาการ");
  const [aiAnnouncePoints, setAiAnnouncePoints] = useState<string>("");
  const [aiAnnounceResult, setAiAnnounceResult] = useState<string>("");
  const [generatingAnnounce, setGeneratingAnnounce] = useState<boolean>(false);

  // Admin Interactive Add/Edit Modals States
  const [editorTarget, setEditorTarget] = useState<'news' | 'teachers' | 'students' | 'downloads' | 'galleries' | 'settings' | null>(null);
  const [selectedItemEditId, setSelectedItemEditId] = useState<string | null>(null);
  
  // Custom News Form
  const [newsFormTitle, setNewsFormTitle] = useState("");
  const [newsFormCategory, setNewsFormCategory] = useState("ข่าวประชาสัมพันธ์ทั่วไป");
  const [newsFormContent, setNewsFormContent] = useState("");
  const [newsFormImage, setNewsFormImage] = useState("");
  
  // Custom Teacher Form
  const [teacherFormName, setTeacherFormName] = useState("");
  const [teacherFormPosition, setTeacherFormPosition] = useState("");
  const [teacherFormLevel, setTeacherFormLevel] = useState("ครูชำนาญการ (คศ.2)");
  const [teacherFormSubject, setTeacherFormSubject] = useState("");
  const [teacherFormImage, setTeacherFormImage] = useState("");

  // Custom Student Form
  const [studentFormName, setStudentFormName] = useState("");
  const [studentFormGrade, setStudentFormGrade] = useState("ประถมศึกษาปีที่ 1");
  const [studentFormClass, setStudentFormClass] = useState("1/1");
  const [studentFormGender, setStudentFormGender] = useState("ชาย");

  // Custom Document Form
  const [docFormTitle, setDocFormTitle] = useState("");
  const [docFormCategory, setDocFormCategory] = useState("เอกสารทั่วไป");
  const [docFormType, setDocFormType] = useState("PDF");
  const [docFormSize, setDocFormSize] = useState("1.5 MB");

  // Custom Gallery Form
  const [galleryTitle, setGalleryTitle] = useState("");
  const [galleryDesc, setGalleryDesc] = useState("");
  const [galleryCover, setGalleryCover] = useState("");

  // School settings form
  const [settingsForm, setSettingsForm] = useState<SchoolSettings | null>(null);

  // Lightbox State
  const [lightboxImage, setLightboxImage] = useState<string | null>(null);

  // Success message toast
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  const triggerToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => setToastMessage(null), 3500);
  };

  // 1. Fetch entire DB state on load
  const loadDatabase = async () => {
    try {
      const res = await fetch("/api/db");
      const data = await res.json();
      setDbState(data);
      if (data.settings) {
        setSettingsForm({ ...data.settings });
      }
      setLoading(false);
    } catch (e) {
      console.error("DB Initialization Error:", e);
      setLoading(false);
    }
  };

  useEffect(() => {
    loadDatabase();
  }, []);

  // 2. Incremental view hit
  const handleReadNews = async (newsId: string) => {
    try {
      const res = await fetch(`/api/db/news/view/${newsId}`, { method: "POST" });
      if (res.ok) {
        const updateVal = await res.json();
        if (dbState) {
          const nextNews = dbState.news.map(n => n.id === newsId ? { ...n, views: updateVal.views } : n);
          setDbState({ ...dbState, news: nextNews });
        }
      }
    } catch {}
  };

  // 3. Document Download Hit
  const handleDownloadDoc = async (docId: string, docUrl: string) => {
    triggerToast("กำลังเตรียมดาวน์โหลดเอกสารความปลอดภัยสูง...");
    try {
      const res = await fetch(`/api/db/downloads/increment/${docId}`, { method: "POST" });
      if (res.ok) {
        const updateVal = await res.json();
        if (dbState) {
          const nextDocs = dbState.downloads.map(d => d.id === docId ? { ...d, downloadCount: updateVal.count } : d);
          setDbState({ ...dbState, downloads: nextDocs });
        }
      }
    } catch {}
    // Open dummy secure preview trigger
    window.open("https://www.orimi.com/pdf-test.pdf", "_blank");
  };

  // 4. Admin LogIn Handler
  const handleAdminLogin = (e: React.FormEvent) => {
    e.preventDefault();
    if (loginUsername === "admin" && loginPassword === "admin123") {
      setIsAdminLoggedIn(true);
      setAuthError("");
      triggerToast("ลงชื่อเข้าใช้ในฐานะผู้ดูแลสูงสุดสำเร็จ ยินดีต้อนรับกลับค่ะ!");
    } else {
      setAuthError("ชื่อผู้ใช้หรือรหัสผ่านหลังบ้านไม่ถูกต้อง กรุณาอ้างอิงข้อมูลผู้ใช้ 'admin' / 'admin123'");
    }
  };

  const handleAdminLogout = () => {
    setIsAdminLoggedIn(false);
    setLoginUsername("");
    setLoginPassword("");
    triggerToast("ออกจากการควบคุมระบบหลังบ้านเรียบร้อย");
  };

  // 5. Ask น้องหว้า AI Chatbot
  const handleAskAi = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!chatMessage.trim()) return;

    const userText = chatMessage;
    setChatMessage("");
    
    const nextHistory = [...chatHistory, { role: 'user' as const, text: userText }];
    setChatHistory(nextHistory);
    setIsAiTyping(true);

    try {
      const res = await fetch("/api/ai/chatbot", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: userText, history: nextHistory }),
      });
      const data = await res.json();
      setChatHistory([...nextHistory, { role: 'model', text: data.text }]);
    } catch (err) {
      setChatHistory([...nextHistory, { role: 'model', text: "ขออภัยด้วยค่ะ ระบบตอบคำถามขัดข้องชั่วคราว ลองพิมพ์สอบถามใหม่อีกครั้งนะคะ" }]);
    } finally {
      setIsAiTyping(false);
    }
  };

  // 6. News AI Auto Summarization
  const handleAiSummarizeNews = async (newsItem: News) => {
    setSummarizingId(newsItem.id);
    try {
      const res = await fetch("/api/ai/summarize", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ content: newsItem.content })
      });
      const data = await res.json();
      setAiSummaries(prev => ({ ...prev, [newsItem.id]: data.summary }));
      triggerToast("AI ได้วิเคราะห์และสรุปเนื้อหาข่าวสารให้อย่างกระชับ!");
    } catch (e) {
      triggerToast("ไม่สามารถประมวลผลสรุปข่าวด้วย AI ได้");
    } finally {
      setSummarizingId(null);
    }
  };

  // 7. AI Document semantic search advisor
  const handleAiDocSearch = async (query: string) => {
    if (!query.trim()) {
      setAiDocAdvice("");
      return;
    }
    setAiDocLoader(true);
    try {
      const res = await fetch("/api/ai/doc-search", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ query })
      });
      const data = await res.json();
      setAiDocAdvice(data.aiRecommendation);
    } catch (error) {
      setAiDocAdvice("ข้อความผิดพลาดขณะเรียกใช้ AI ในการค้นหาเอกสาร");
    } finally {
      setAiDocLoader(false);
    }
  };

  // 8. AI Instant announcement engine
  const handleAiGenerateAnnouncement = async () => {
    if (!aiAnnounceTitle.trim() || !aiAnnouncePoints.trim()) {
      triggerToast("กรุณากรอกหัวข้ออย่างน้อย 1 อย่างเพื่อเขียนรายงานประกาศ");
      return;
    }
    setGeneratingAnnounce(true);
    try {
      const res = await fetch("/api/ai/generate-announcement", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          title: aiAnnounceTitle,
          category: aiAnnounceCategory,
          keyPoints: aiAnnouncePoints
        })
      });
      const data = await res.json();
      setAiAnnounceResult(data.text);
      triggerToast("AI ช่วยร่างหนังสือทางการโรงเรียนเสร็จสิ้น!");
    } catch (e) {
      triggerToast("ขัดข้องขณะให้ระบบ AI เขียนคำทักทายประกาศ");
    } finally {
      setGeneratingAnnounce(false);
    }
  };

  // 9. SQL Database Operations via Express HTTP Simulated APIs
  const saveNewsForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newsFormTitle.trim() || !newsFormContent.trim()) {
      alert("กรุณากรอกหัวข้อและเนื้อหาข่าวให้เรียบร้อย");
      return;
    }

    const payload = {
      title: newsFormTitle,
      category: newsFormCategory,
      content: newsFormContent,
      imageUrl: newsFormImage || "https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&q=80&w=600",
      date: new Date().toISOString().split("T")[0]
    };

    try {
      let url = "/api/db/news";
      let method = "POST";
      if (selectedItemEditId) {
        url = `/api/db/news/${selectedItemEditId}`;
        method = "PUT";
      }

      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        triggerToast(selectedItemEditId ? "แก้ไขข่าวสารสำเร็จ" : "เพิ่มข่าวประชาสัมพันธ์ชิ้นใหม่สำเร็จ");
        loadDatabase();
        setEditorTarget(null);
        setSelectedItemEditId(null);
        // Reset forms
        setNewsFormTitle("");
        setNewsFormContent("");
        setNewsFormImage("");
      }
    } catch (err) {
      alert("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
    }
  };

  const deleteNews = async (id: string) => {
    if (!confirm("ยืนยันที่จะลบข่าวประชาสัมพันธ์ชิ้นนี้ใช่หรือไม่? ข้อมูลใน MySQL จำลองจะถูกย้ายออกถาวร")) return;
    try {
      const res = await fetch(`/api/db/news/${id}`, { method: "DELETE" });
      if (res.ok) {
        triggerToast("ลบข้อมูลข่าวประชาสัมพันธ์ออกแล้ว");
        loadDatabase();
      }
    } catch {}
  };

  const saveTeacherForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!teacherFormName.trim() || !teacherFormPosition.trim()) {
      alert("กรุณากรอกชื่อและตำแหน่งของครูและบุคลากร");
      return;
    }

    const payload = {
      name: teacherFormName,
      position: teacherFormPosition,
      level: teacherFormLevel,
      subjectGroup: teacherFormSubject || "กลุ่มสาระการเรียนรู้",
      imageUrl: teacherFormImage || "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=300",
    };

    try {
      let url = "/api/db/teachers";
      let method = "POST";
      if (selectedItemEditId) {
        url = `/api/db/teachers/${selectedItemEditId}`;
        method = "PUT";
      }

      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        triggerToast(selectedItemEditId ? "อัปเดตข้อมูลบุคลากรสำเร็จ" : "บันทึกรายชื่อครูคนใหม่เรียบร้อย");
        loadDatabase();
        setEditorTarget(null);
        setSelectedItemEditId(null);
        setTeacherFormName("");
        setTeacherFormPosition("");
        setTeacherFormSubject("");
        setTeacherFormImage("");
      }
    } catch {}
  };

  const deleteTeacher = async (id: string) => {
    if (!confirm("ลบรายชื่อบุคลากรครูจากระบบใช่หรือไม่?")) return;
    try {
      const res = await fetch(`/api/db/teachers/${id}`, { method: "DELETE" });
      if (res.ok) {
        triggerToast("ลบรายชื่อบุคลากรเรียบร้อย");
        loadDatabase();
      }
    } catch {}
  };

  const saveStudentForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!studentFormName.trim() || !studentFormClass.trim()) {
      alert("กรุณากรอกชื่อนักเรียนและห้องเรียน");
      return;
    }

    const payload = {
      name: studentFormName,
      grade: studentFormGrade,
      classroom: studentFormClass,
      gender: studentFormGender
    };

    try {
      let url = "/api/db/students";
      let method = "POST";
      if (selectedItemEditId) {
        url = `/api/db/students/${selectedItemEditId}`;
        method = "PUT";
      }

      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        triggerToast("บันทึกข้อมูลข้อมูลประวัตินักเรียนสำเร็จ");
        loadDatabase();
        setEditorTarget(null);
        setSelectedItemEditId(null);
        setStudentFormName("");
        setStudentFormClass("");
      }
    } catch {}
  };

  const deleteStudent = async (id: string) => {
    if (!confirm("ต้องการนำรายชื่อนักเรียนคนนี้ออกจากระบบใช่หรือไม่?")) return;
    try {
      const res = await fetch(`/api/db/students/${id}`, { method: "DELETE" });
      if (res.ok) {
        triggerToast("ลบข้อมูลนักเรียนแล้ว");
        loadDatabase();
      }
    } catch {}
  };

  const saveDocForm = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!docFormTitle.trim()) {
      alert("กรุณาใส่ชื่อเอกสาร");
      return;
    }

    const payload = {
      title: docFormTitle,
      category: docFormCategory,
      fileType: docFormType,
      fileSize: docFormSize,
    };

    try {
      let url = "/api/db/downloads";
      let method = "POST";
      if (selectedItemEditId) {
        url = `/api/db/downloads/${selectedItemEditId}`;
        method = "PUT";
      }

      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        triggerToast("อัปโหลดและลงทะเบียนลิงค์เอกสารเรียบร้อย");
        loadDatabase();
        setEditorTarget(null);
        setSelectedItemEditId(null);
        setDocFormTitle("");
      }
    } catch {}
  };

  const deleteDoc = async (id: string) => {
    if (!confirm("ต้องการลบเอกสารดาวน์โหลดฉบับนี้?")) return;
    try {
      const res = await fetch(`/api/db/downloads/${id}`, { method: "DELETE" });
      if (res.ok) {
        triggerToast("ลบข้อมูลเอกสารเผยแพร่เรียบร้อย");
        loadDatabase();
      }
    } catch {}
  };

  const saveSettings = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!settingsForm) return;

    try {
      const res = await fetch("/api/db/settings", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(settingsForm)
      });
      if (res.ok) {
        triggerToast("บันทึกการตั้งค่าข้อมูลจำเพาะโรงเรียนเรียบร้อยแล้ว");
        loadDatabase();
        setEditorTarget(null);
      }
    } catch (error) {
      alert("ไม่สามารถแก้การตั้งค่าได้");
    }
  };

  // Quick setup for edit mode filled values
  const setupEditNews = (item: News) => {
    setSelectedItemEditId(item.id);
    setNewsFormTitle(item.title);
    setNewsFormCategory(item.category);
    setNewsFormContent(item.content);
    setNewsFormImage(item.imageUrl);
    setEditorTarget('news');
  };

  const setupEditTeacher = (item: Teacher) => {
    setSelectedItemEditId(item.id);
    setTeacherFormName(item.name);
    setTeacherFormPosition(item.position);
    setTeacherFormLevel(item.level);
    setTeacherFormSubject(item.subjectGroup || "");
    setTeacherFormImage(item.imageUrl);
    setEditorTarget('teachers');
  };

  const setupEditStudent = (item: Student) => {
    setSelectedItemEditId(item.id);
    setStudentFormName(item.name);
    setStudentFormGrade(item.grade);
    setStudentFormClass(item.classroom);
    setStudentFormGender(item.gender);
    setEditorTarget('students');
  };

  const setupEditDoc = (item: DownloadDoc) => {
    setSelectedItemEditId(item.id);
    setDocFormTitle(item.title);
    setDocFormCategory(item.category);
    setDocFormType(item.fileType);
    setDocFormSize(item.fileSize);
    setEditorTarget('downloads');
  };

  // Contacts mock storage send
  const [contactName, setContactName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [contactSubject, setContactSubject] = useState("");
  const [contactMessage, setContactMessage] = useState("");
  
  const handleContactSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!contactName || !contactMessage) {
      alert("กรุณากรอกชื่อและข้อความที่จะติดต่อครูผู้รับผิดชอบ");
      return;
    }
    alert(`ส่งข้อความจาก "${contactName}" ไปยังกลุ่มสาระนโยบายและแผนเรียบร้อย!\nเจ้าหน้าที่จะติดต่อกลับด่วนทางช่องทาง ${contactEmail || "โทรศัพท์"}`);
    setContactName("");
    setContactEmail("");
    setContactSubject("");
    setContactMessage("");
    triggerToast("ส่งข้อความถึงแอดมินโรงเรียนบ้านหนองหว้าแล้วค่ะ");
  };

  // Render view helpers
  if (loading || !dbState) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-50 flex-col gap-4">
        <div className="h-12 w-12 animate-spin rounded-full border-4 border-school-red border-t-school-yellow"></div>
        <p className="font-medium text-slate-600 animate-pulse text-sm">กำลังเปิดระบบหลังบ้านและคลังข้อมูลโรงเรียนบ้านหนองหว้า...</p>
      </div>
    );
  }

  const { settings, banners, news, teachers, students, downloads, galleries } = dbState;

  // Filters logic
  const filteredNews = news.filter(n => {
    const matchesSearch = n.title.toLowerCase().includes(newsSearch.toLowerCase()) || 
                          n.content.toLowerCase().includes(newsSearch.toLowerCase());
    const matchesCat = newsCategory === "ทั้งหมด" || n.category === newsCategory;
    return matchesSearch && matchesCat;
  });

  const filteredTeachers = teachers.filter(t => {
    const matchesSearch = t.name.toLowerCase().includes(teacherSearch.toLowerCase()) || 
                          t.position.toLowerCase().includes(teacherSearch.toLowerCase());
    const matchesLevel = teacherFilter === "ทั้งหมด" || t.level.includes(teacherFilter) || t.subjectGroup?.includes(teacherFilter);
    return matchesSearch && matchesLevel;
  }).sort((a,b) => a.order - b.order);

  const filteredStudents = students.filter(s => {
    const matchesSearch = s.name.toLowerCase().includes(studentSearch.toLowerCase());
    const matchesGrade = studentGrade === "ทั้งหมด" || s.grade.includes(studentGrade);
    return matchesSearch && matchesGrade;
  });

  const filteredDocs = downloads.filter(d => {
    const matchesSearch = d.title.toLowerCase().includes(docSearch.toLowerCase());
    const matchesCat = docCategory === "ทั้งหมด" || d.category === docCategory;
    return matchesSearch && matchesCat;
  });

  return (
    <div className="min-h-screen bg-slate-50 font-sans text-slate-800 flex flex-col selection:bg-school-yellow selection:text-school-red-dark">
      
      {/* Dynamic Toast Status Notification Banner */}
      {toastMessage && (
        <div className="fixed bottom-6 right-6 z-50 rounded-2xl bg-slate-900 border border-slate-700/80 px-5 py-4 text-white shadow-2xl flex items-center gap-3 animate-bounce max-w-sm">
          <Sparkles className="h-5 w-5 text-school-yellow animate-spin shrink-0" />
          <div>
            <div className="text-xs font-bold text-amber-300">แจ้งเตือนสถานะเว็บ</div>
            <div className="text-xs font-semibold leading-relaxed mt-0.5">{toastMessage}</div>
          </div>
          <button onClick={() => setToastMessage(null)} className="ml-auto text-slate-400 hover:text-white">
            <X className="h-4 w-4" />
          </button>
        </div>
      )}

      {/* HEADER BAR (School Theme Color scheme: Red / yellow accents) */}
      <header className="bg-school-red text-white sticky top-0 z-40 shadow-md border-b-4 border-school-yellow transition-all">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col md:flex-row items-center justify-between gap-4">
          
          {/* Logo & School Motto (Vibrant theme style) */}
          <div className="flex items-center gap-3.5 cursor-pointer" onClick={() => setActiveTab("home")}>
            <div className="w-12 h-12 bg-white rounded-full flex items-center justify-center border-2 border-school-yellow shadow-inner">
              <span className="text-school-red font-black text-lg tracking-tighter">น.ห.</span>
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-bold tracking-tight text-white mb-0">{settings.schoolName}</h1>
                <span className="text-[10px] bg-school-yellow text-school-red-dark rounded-md px-1.5 py-0.5 font-bold">บุรีรัมย์ เขต 3</span>
              </div>
              <p className="text-[11px] text-amber-200 tracking-wider font-medium font-heading">
                แหล่งวิทยาการ กีฬาเด่น เน้นคุณธรรม สัมพันธ์ชุมชน | สพป. บุรีรัมย์ เขต 3
              </p>
            </div>
          </div>

          {/* Nav Links */}
          <nav className="flex flex-wrap items-center justify-center gap-1 md:gap-2 text-[13px] font-medium">
            <button 
              onClick={() => { setActiveTab("home"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all ${activeTab === "home" ? "bg-school-yellow text-school-red-dark font-bold shadow-sm" : "hover:bg-school-red-light/40"}`}
            >
              หน้าแรก
            </button>
            <button 
              onClick={() => { setActiveTab("news"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all ${activeTab === "news" ? "bg-school-yellow text-school-red-dark font-bold shadow-sm" : "hover:bg-school-red-light/40"}`}
            >
              ข่าวประชาสัมพันธ์
            </button>
            <button 
              onClick={() => { setActiveTab("teachers"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all ${activeTab === "teachers" ? "bg-school-yellow text-school-red-dark font-bold shadow-sm" : "hover:bg-school-red-light/40"}`}
            >
              ทำเนียบครู
            </button>
            <button 
              onClick={() => { setActiveTab("students"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all ${activeTab === "students" ? "bg-school-yellow text-school-red-dark font-bold shadow-sm" : "hover:bg-school-red-light/40"}`}
            >
              ข้อมูลนักเรียน
            </button>
            <button 
              onClick={() => { setActiveTab("downloads"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all ${activeTab === "downloads" ? "bg-school-yellow text-school-red-dark font-bold shadow-sm" : "hover:bg-school-red-light/40"}`}
            >
              ดาวน์โหลดเอกสาร
            </button>
            <button 
              onClick={() => { setActiveTab("galleries"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all ${activeTab === "galleries" ? "bg-school-yellow text-school-red-dark font-bold shadow-sm" : "hover:bg-school-red-light/40"}`}
            >
              แกลเลอรีภาพ
            </button>
            <button 
              onClick={() => { setActiveTab("developer"); window.scrollTo(0,0); }}
              className={`px-3 py-1.5 rounded-lg transition-all text-xs border border-amber-400 border-dashed ${activeTab === "developer" ? "bg-amber-400 text-slate-900 font-bold" : "text-amber-200 hover:bg-white/10"}`}
            >
              <FileCode className="h-3.5 w-3.5 inline mr-1" />
              โค้ด & SQL สำเร็จรูป
            </button>
          </nav>

          {/* AI Active Indicator & Admin Center Switch button */}
          <div className="flex items-center gap-2.5">
            <div className="hidden lg:flex items-center gap-1.5 bg-school-red-dark px-3 py-1 rounded-full text-[10px] text-emerald-300 font-sans border border-school-red-light">
              <span className="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
              น้องหว้า AI Chatbot Active
            </div>

            {isAdminLoggedIn ? (
              <div className="flex items-center gap-2">
                <button 
                  onClick={() => setActiveTab("admin")}
                  className="bg-amber-400 text-slate-900 text-xs font-bold px-3 py-1.5 rounded-lg hover:bg-amber-300 flex items-center gap-1"
                >
                  <Unlock className="h-3.5 w-3.5" />
                  ควบคุมระบบ
                </button>
                <button 
                  onClick={handleAdminLogout}
                  className="bg-slate-950/70 text-slate-100 text-xs px-2.5 py-1.5 rounded-lg hover:bg-slate-950"
                  title="ออกจากระบบแอดมิน"
                >
                  ออก
                </button>
              </div>
            ) : (
              <button 
                onClick={() => { setActiveTab("admin"); window.scrollTo(0,0); }}
                className="bg-amber-400 hover:bg-amber-300 text-school-red-dark font-black text-xs px-4 py-2 rounded-xl transition-all shadow-md active:scale-95"
              >
                ADMIN LOGIN
              </button>
            )}
          </div>

        </div>
      </header>

      {/* MAIN LAYOUT WRAPPER */}
      <main className="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {/* TAB 1: HOME PANEL */}
        {activeTab === "home" && (
          <div className="space-y-12">
            
            {/* HERO HERO & STATISTICS SECTION */}
            <div className="relative rounded-3xl overflow-hidden shadow-xl bg-slate-900 text-white min-h-[380px] flex items-center">
              
              {/* Background cover image with custom beautiful layout opacity */}
              <div className="absolute inset-0 bg-cover bg-center opacity-30 z-0 bg-[url('https://images.unsplash.com/photo-1546410531-bb4caa6b424d?auto=format&fit=crop&q=80&w=1200')]"></div>
              <div className="absolute inset-0 bg-gradient-to-r from-school-red-dark via-slate-950/90 to-transparent z-10"></div>
              
              <div className="relative z-20 max-w-3xl px-8 sm:px-12 py-12 space-y-6">
                <span className="inline-block bg-white text-school-pink text-[10px] tracking-widest uppercase font-black px-3.5 py-1.5 rounded-full shadow-sm">
                  ยินดีต้อนรับสู่รั้วชมพู-ขาว แหล่งการศึกษาระดับเยาวชนต้นแบบ
                </span>
                
                <h2 className="text-4xl sm:text-5xl font-black leading-tight text-white">
                  มุ่งเน้นเสริมสร้างคุณธรรม <br />
                  <span className="text-school-yellow">พัฒนาเยาวชน ทันเทคโนโลยี</span>
                </h2>
                
                <p className="text-slate-200 text-sm sm:text-base max-w-xl leading-relaxed">
                  โรงเรียนบ้านหนองหว้า ต.หนองกี่ อ.หนองกี่ จ.บุรีรัมย์ เปิดทำการเรียนการสอนระดับปฐมวัย 
                  จนถึงระดับประถมศึกษาปีที่ 6 เราส่งเสริมสมรรถนะผู้เรียนในด้านวิชาการ กีฬา ประเพณีไทย และระบบคิดวิเคราะห์อย่างรอบด้าน
                </p>

                <div className="flex flex-wrap gap-4 pt-2">
                  <button 
                    onClick={() => setActiveTab("news")}
                    className="bg-school-yellow hover:bg-amber-400 text-slate-950 font-black text-sm px-6 py-3 rounded-2xl transition shadow-lg"
                  >
                    อ่านข่าวประชาสัมพันธ์
                  </button>
                  <button 
                    onClick={() => setActiveTab("downloads")}
                    className="bg-white/10 hover:bg-white/20 text-white border border-white/25 text-sm px-6 py-3 rounded-2xl transition"
                  >
                    ดาวน์โหลดใบสมัครปี 2569
                  </button>
                </div>
              </div>

              {/* Float Widget with School Metrics Database Counters */}
              <div className="absolute bottom-6 right-6 z-20 hidden md:flex items-center gap-4 bg-slate-900/90 backdrop-blur-md p-5 rounded-3xl border border-slate-700 shadow-2xl">
                <div className="text-center px-4 border-r border-slate-700">
                  <div className="text-3xl font-black text-school-yellow">{students.length + 337}</div>
                  <div className="text-[10px] text-slate-300 font-medium">นักเรียนลงทะเบียน</div>
                </div>
                <div className="text-center px-4 border-r border-slate-700">
                  <div className="text-3xl font-black text-school-yellow">{teachers.length + 6}</div>
                  <div className="text-[10px] text-slate-300 font-medium">บุคลากรทางการศึกษา</div>
                </div>
                <div className="text-center px-4">
                  <div className="text-3.5 text-xl font-bold text-emerald-400">● 100%</div>
                  <div className="text-[10px] text-slate-300 font-medium">ความปลอดภัย</div>
                </div>
              </div>

            </div>

            {/* THREE PANELS GRID: CHATBOT + PRINCIPAL DIRECT DECK + DIRECT QUICK CONTROL */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
              
              {/* PANEL 1: AI NONG WA CHATBOT INTERPLAY */}
              <div className="lg:col-span-8 flex flex-col bg-white rounded-3xl border border-slate-100 shadow-md overflow-hidden">
                <div className="bg-gradient-to-r from-school-red to-school-red-dark p-5 text-white flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="p-2.5 bg-white/10 rounded-xl">
                      <Bot className="h-6 w-6 text-school-yellow animate-bounce" />
                    </div>
                    <div>
                      <h3 className="font-bold text-base text-white">น้องหว้า AI • ผู้ช่วยปรึกษาการศึกษาและข้อมูลจำลอง</h3>
                      <p className="text-[11px] text-amber-200">ถาม-ตอบแบบเรียลไทม์ เชื่อมโยงบริบทข่าว, คณะครู และประวัตินักเรียน</p>
                    </div>
                  </div>
                  <span className="text-[10px] bg-emerald-500/20 text-emerald-300 border border-emerald-500 px-2.5 py-0.5 rounded-full font-bold">
                    ONLINE
                  </span>
                </div>

                {/* Chat Message Lists */}
                <div className="p-6 flex-1 max-h-[300px] overflow-y-auto space-y-4 min-h-[220px] bg-slate-50/50">
                  {chatHistory.map((msg, i) => (
                    <div key={i} className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}>
                      <div className={`max-w-md rounded-2xl px-4 py-3 text-xs leading-relaxed shadow-sm ${
                        msg.role === 'user' 
                          ? 'bg-school-red text-white rounded-br-none' 
                          : 'bg-white text-slate-800 rounded-bl-none border border-slate-100'
                      }`}>
                        {msg.text}
                      </div>
                    </div>
                  ))}
                  {isAiTyping && (
                    <div className="flex justify-start">
                      <div className="bg-slate-200 text-slate-600 rounded-2xl rounded-bl-none px-4 py-2 text-[11px] animate-pulse">
                        น้องหว้า AI กำลังพิมพ์คัดกรองข้อมูลโรงเรียน...
                      </div>
                    </div>
                  )}
                </div>

                {/* Input prompt query */}
                <div className="p-4 border-t border-slate-100 bg-white">
                  <form onSubmit={handleAskAi} className="flex gap-2">
                    <input 
                      type="text"
                      value={chatMessage}
                      onChange={(e) => setChatMessage(e.target.value)}
                      placeholder="ลองสอบถาม เช่น 'ผู้อำนวยการชื่ออะไร', 'โรงเรียนสอนระดับชั้นไหนบ้าง', 'รายงานข่าวเปิดภาคเรียนมีข้อมูลอย่างไร'"
                      className="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-xs focus:ring-2 focus:ring-school-red focus:outline-none"
                    />
                    <button 
                      type="submit"
                      className="bg-school-red hover:bg-school-red-dark text-white px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1 shrink-0"
                    >
                      <Sparkles className="h-3.5 w-3.5 text-school-yellow" />
                      ส่งคำถาม
                    </button>
                  </form>
                </div>
              </div>

              {/* PANEL 2: PRINCIPAL MASSAGE & WORD OF EDUCATION */}
              <div className="lg:col-span-4 flex flex-col bg-white rounded-3xl border border-slate-100 p-6 shadow-md items-center text-center relative overflow-hidden group">
                <div className="absolute top-0 left-0 w-full h-2.5 bg-gradient-to-r from-school-red to-school-yellow"></div>
                
                <div className="relative mt-4">
                  <div className="w-24 h-24 rounded-full overflow-hidden border-4 border-school-yellow shadow-lg">
                    <img 
                      src={settings.directorImage} 
                      alt="ผู้อำนวยการโรงเรียนบ้านหนองหว้า"
                      className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                    />
                  </div>
                  <span className="absolute bottom-0 right-0 h-5 w-5 rounded-full bg-emerald-500 border-2 border-white" title="Active"></span>
                </div>

                <h4 className="font-bold text-slate-800 text-base mt-4 leading-tight">
                  {settings.directorName}
                </h4>
                <p className="text-xs text-school-red font-bold uppercase tracking-wider mt-1">
                  {settings.directorTitle}
                </p>

                <div className="relative mt-4 bg-slate-50/80 rounded-2xl p-4 border border-slate-100/50">
                  <span className="absolute -top-3 left-4 text-slate-300 font-serif text-4xl">“</span>
                  <p className="text-[11px] text-slate-600 italic leading-relaxed px-2">
                    สวัสดียินดีสู่ขอบเขตนวัตกรรมการศึกษาเบื้องต้น บากบั่นมุ่งมั่นต่อยอดความรู้ และสร้างสมองเด็กดีพร้อมความกตัญญูกตเวทิตาในการคืนพลังงานสู่ท้องถิ่น
                  </p>
                </div>

                <div className="w-full mt-6 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-left">
                  <div className="bg-slate-50 p-2.5 rounded-xl text-center">
                    <span className="text-[10px] text-slate-400 block">เบอร์ทางตรง</span>
                    <strong className="text-xs text-slate-700">{settings.phone}</strong>
                  </div>
                  <div className="bg-slate-50 p-2.5 rounded-xl text-center">
                    <span className="text-[10px] text-slate-400 block">สถิติสะสม</span>
                    <strong className="text-xs text-slate-700">{settings.visitorCount} ครั้ง</strong>
                  </div>
                </div>
              </div>

            </div>

            {/* INTERACTIVE LATEST NEWS SLATE & RECENT EVENT PREVIEWS */}
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-xl font-bold flex items-center gap-2 text-slate-800">
                    <span className="w-2.5 h-6 bg-school-red rounded-full"></span> 
                    ข่าวประกาศสำคัญและกิจกรรมโรงเรียนบ้านหนองหว้า
                  </h3>
                  <p className="text-xs text-slate-500">อัปเดตสม่ำเสมอ สามารถเลือกดูรายข่าวพร้อมใช้ระบบย่อข่าวสรุปอัจฉริยะ AI ในหน้าต่าง</p>
                </div>
                <button 
                  onClick={() => setActiveTab("news")}
                  className="text-xs text-school-red font-bold hover:underline bg-school-red/10 px-3.5 py-2 rounded-xl transition"
                >
                  ข่าวทั้งหมด →
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {news.slice(0, 3).map((item) => (
                  <div 
                    key={item.id} 
                    className="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden group hover:shadow-md hover:border-school-red/20 transition-all flex flex-col"
                  >
                    <div className="relative h-44 overflow-hidden bg-slate-100">
                      <img 
                        src={item.imageUrl} 
                        alt={item.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                        loading="lazy"
                      />
                      <span className="absolute top-3 left-3 bg-school-red text-white text-[10px] font-black px-2.5 py-1 rounded-full uppercase">
                        {item.category}
                      </span>
                    </div>

                    <div className="p-5 flex-grow space-y-3 flex flex-col justify-between">
                      <div className="space-y-2">
                        <div className="flex items-center justify-between text-[10px] text-slate-400">
                          <span className="flex items-center gap-1">
                            <Calendar className="h-3.5 w-3.5 text-school-yellow-dark" />
                            {item.date}
                          </span>
                          <span className="flex items-center gap-1">
                            <Eye className="h-3.5 w-3.5 text-slate-400" />
                            {item.views} วิว
                          </span>
                        </div>
                        <h4 className="font-bold text-sm text-slate-800 leading-snug line-clamp-2">
                          {item.title}
                        </h4>
                        
                        {/* Summary news area */}
                        <div className="bg-slate-50 rounded-xl p-3 text-[11px] text-slate-500 leading-relaxed min-h-[50px] border border-slate-100">
                          {aiSummaries[item.id] ? (
                            <span className="text-emerald-700 font-semibold">
                              <Sparkles className="h-3 w-3 inline text-school-yellow mr-1" />
                              สรุปโดย AI: {aiSummaries[item.id]}
                            </span>
                          ) : (
                            <span>{item.summary || item.content.substring(0, 80) + "..."}</span>
                          )}
                        </div>
                      </div>

                      <div className="pt-3 border-t border-slate-100 mt-2 flex items-center justify-between">
                        <button 
                          onClick={() => {
                            handleReadNews(item.id);
                            setupEditNews(item);
                            setActiveTab("news");
                          }}
                          className="text-xs font-bold text-school-red hover:text-school-red-dark flex items-center gap-1"
                        >
                          อ่านเนื้อหาเต็ม →
                        </button>

                        <button 
                          onClick={() => handleAiSummarizeNews(item)}
                          disabled={summarizingId === item.id}
                          className="bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-lg px-2.5 py-1 text-[10px] font-bold flex items-center gap-1 transition"
                        >
                          <Sparkles className="h-3 w-3 text-emerald-600 animate-spin" />
                          {summarizingId === item.id ? "กำลังสรุป..." : "AI ย่อด่วน"}
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* EMBEDDED INTRODUCTORY VIDEO & SCHOOL GALLERY GRID */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 pt-4">
              
              {/* VIDEO INTRO STYLISH WRAP */}
              <div className="bg-white rounded-3xl border border-slate-100 p-6 shadow-md space-y-4">
                <h3 className="text-base font-bold text-slate-800 flex items-center gap-1.5">
                  <Tv className="h-5 w-5 text-school-red" />
                  วิดีทัศน์แนะนำโรงเรียนบ้านหนองหว้า
                </h3>
                <p className="text-xs text-slate-500 leading-relaxed">
                  สัมผัสบรรยากาศความสวยงาม อาคารเรียน การทำกิจกรรมของสภานักเรียน และผลลัพธ์ของเยาวชนคนเก่งของเราในโครงการพัฒนาสิ่งแวดล้อม
                </p>
                <div className="relative rounded-2xl overflow-hidden aspect-video bg-slate-950 shadow-inner">
                  <iframe 
                    className="absolute inset-0 w-full h-full border-0"
                    src={settings.youtubeIntroUrl || "https://www.youtube.com/embed/gCOk8X63Rpk"}
                    title="วิดีโอแนะนำโรงเรียนบ้านหนองหว้า" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowFullScreen
                  ></iframe>
                </div>
              </div>

              {/* PHOTO ALBUMS LIGHTBOX */}
              <div className="bg-white rounded-3xl border border-slate-100 p-6 shadow-md space-y-4">
                <div className="flex items-center justify-between">
                  <h3 className="text-base font-bold text-slate-800 flex items-center gap-1.5">
                    <ImageIcon className="h-5 w-5 text-school-yellow-dark" />
                    แกลเลอรีภาพกิจกรรมล่าสุด
                  </h3>
                  <button onClick={() => setActiveTab("galleries")} className="text-xs font-bold text-school-red hover:underline">
                    เว็บบอร์ดภาพทั้งหมด
                  </button>
                </div>
                <p className="text-xs text-slate-500">
                  รวบรวมช่วงเวลาความประทับใจ การประกวดโครงงาน และมหกรรมกีฬาภายใน 'หนองหว้าเกมส์' (คลิกดูภาพขยายขนาดใหญ่ได้)
                </p>
                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  {galleries.slice(0, 1).map(gal => (
                    gal.images.map((img, i) => (
                      <div 
                        key={i} 
                        onClick={() => setLightboxImage(img)}
                        className="relative rounded-xl overflow-hidden aspect-square bg-slate-100 cursor-pointer group border border-slate-100"
                      >
                        <img 
                          src={img} 
                          alt="ภาพกิจกรรมโรงเรียนบ้านหนองหว้า"
                          className="w-full h-full object-cover group-hover:scale-105 transition"
                          loading="lazy"
                        />
                        <div className="absolute inset-0 bg-slate-950/20 group-hover:bg-slate-950/0 transition"></div>
                      </div>
                    ))
                  ))}
                </div>
              </div>

            </div>

            {/* QUICK LINK FOOT-WIDGET DECK */}
            <div className="rounded-3xl bg-amber-400 p-8 text-slate-900 border-2 border-school-yellow shadow-md flex flex-col md:flex-row items-center justify-between gap-6">
              <div className="space-y-2 max-w-xl">
                <h4 className="text-xl font-bold tracking-tight">กำลังมองหาแบบฟอร์มใบสมัครเรียนอยู่ใช่ไหมคะ?</h4>
                <p className="text-xs text-slate-800 leading-relaxed font-heading">
                  คุณสามารถดาวน์โหลดใบสมัครเข้าเรียน ไฟล์ประวัตินักเรียน และเอกสารรายงานประจำปี (SAR) ทั้งรูปแบบ PDF และไฟล์ Word เพื่อใช้เป็นเครื่องมือเรียนรู้เพิ่มเติม
                </p>
              </div>
              <button 
                onClick={() => setActiveTab("downloads")}
                className="bg-school-red hover:bg-school-red-dark text-white font-black text-xs px-6 py-3 rounded-2xl transition shadow-lg shrink-0 flex items-center gap-2"
              >
                <FileDown className="h-4 w-4" />
                เข้าห้องสมุดดาวน์โหลด
              </button>
            </div>

          </div>
        )}

        {/* TAB 2: NEWS DIRECTORY PANEL */}
        {activeTab === "news" && (
          <div className="space-y-8">
            
            {/* Header intro */}
            <div className="bg-gradient-to-r from-school-red to-school-red-dark rounded-3xl p-8 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 shadow">
              <div className="space-y-2">
                <h2 className="text-2xl sm:text-3xl font-black text-school-yellow">ระบบศูนย์วิจัยข่าวประชาสัมพันธ์และกิจกรรม</h2>
                <p className="text-xs text-slate-100 leading-relaxed">
                  โรงเรียนบ้านหนองหว้า มอบหมายให้สภานักเรียนและกลุ่มสาระจัดการข้อมูลอย่างเป็นทางการที่นี่
                </p>
              </div>
              <div className="bg-white/10 p-3 rounded-2xl border border-white/20 text-center">
                <span className="text-[10px] block text-amber-200">จำนวนข่าวสารปัจจุบัน</span>
                <strong className="text-lg text-white font-black">{news.length} รายการ</strong>
              </div>
            </div>

            {/* Filter Tools & Search Form */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
              <div className="relative w-full sm:w-80">
                <span className="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                  <Search className="h-4 w-4" />
                </span>
                <input 
                  type="text"
                  value={newsSearch}
                  onChange={(e) => setNewsSearch(e.target.value)}
                  placeholder="ค้นหาชื่อข่าว ประชาสัมพันธ์ กิจกรรม..."
                  className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-school-red"
                />
              </div>

              <div className="flex gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                {["ทั้งหมด", "ประชาสัมพันธ์ทั่วไป", "ข่าวกิจกรรม", "ประชุมและวิชาการ"].map((cat) => (
                  <button
                    key={cat}
                    onClick={() => setNewsCategory(cat)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 transition ${
                      newsCategory === cat ? 'bg-school-red text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    {cat}
                  </button>
                ))}
              </div>
            </div>

            {/* News Render Board (Full detail card or edit/delete options in mock DB) */}
            {filteredNews.length === 0 ? (
              <div className="text-center py-12 bg-white rounded-3xl border border-dashed border-slate-200">
                <AlertTriangle className="h-10 w-10 text-amber-500 mx-auto mb-3" />
                <h4 className="font-bold text-sm text-slate-700">ไม่พบหัวข้อข่าวประชาสัมพันธ์ตามเงื่อนไขที่คุณค้นหา</h4>
                <p className="text-xs text-slate-500 mt-1">ลองเปลี่ยนแปลงคำค้นหา หรือกดเลือกหมวดหมู่ใหม่อีกครั้งค่ะ</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                {filteredNews.map((item) => (
                  <article 
                    key={item.id} 
                    className="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm flex flex-col justify-between group hover:shadow-md transition"
                  >
                    <div className="relative h-56 bg-slate-100 overflow-hidden">
                      <img src={item.imageUrl} alt={item.title} className="w-full h-full object-cover group-hover:scale-105 transition" />
                      <div className="absolute top-4 left-4 bg-school-yellow text-slate-950 font-black text-[10px] px-3 py-1 rounded-full uppercase shadow">
                        {item.category}
                      </div>
                    </div>

                    <div className="p-6 space-y-4 flex-grow flex flex-col justify-between">
                      <div className="space-y-3">
                        <div className="flex items-center gap-3 text-[11px] text-slate-400">
                          <span>วันที่: <strong>{item.date}</strong></span>
                          <span>•</span>
                          <span>ผู้ชม: <strong>{item.views} ครั้ง</strong></span>
                        </div>
                        
                        <h3 className="font-bold text-base text-slate-800 leading-snug">
                          {item.title}
                        </h3>

                        <p className="text-xs text-slate-600 leading-relaxed">
                          {item.content}
                        </p>

                        {/* Artificial Summary presentation */}
                        <div className="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-xs font-heading">
                          <div className="flex items-center gap-1.5 text-emerald-800 font-bold mb-1.5">
                            <Sparkles className="h-4 w-4 text-emerald-600" />
                            <span>ย่อความสรุปข่าว (สนับสนุนโดย น้องหว้า AI)</span>
                          </div>
                          <p className="text-slate-600 text-xs leading-relaxed italic">
                            {aiSummaries[item.id] || "กดปุ่มเพื่อใช้ AI ถอดใจความสำคัญของประกาศฉบับเต็มโดยไว"}
                          </p>
                          {!aiSummaries[item.id] && (
                            <button 
                              onClick={() => handleAiSummarizeNews(item)}
                              disabled={summarizingId === item.id}
                              className="mt-2 text-[10px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg"
                            >
                              {summarizingId === item.id ? "กำลังประมวลผล..." : "ย่อข่าวสั้นกระชับ"}
                            </button>
                          )}
                        </div>
                      </div>

                      {isAdminLoggedIn && (
                        <div className="pt-4 border-t border-slate-100 mt-2 flex items-center gap-2">
                          <button 
                            onClick={() => setupEditNews(item)}
                            className="text-xs bg-amber-100 hover:bg-amber-200 text-slate-800 px-3 py-1.5 rounded-xl border border-amber-300 font-semibold flex items-center gap-1"
                          >
                            <Edit2 className="h-3.5 w-3.5" />
                            แก้ไขข่าว
                          </button>
                          <button 
                            onClick={() => deleteNews(item.id)}
                            className="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 px-3 py-1.5 rounded-xl flex items-center gap-1 border border-rose-200"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                            ลบข่าว
                          </button>
                        </div>
                      )}
                    </div>
                  </article>
                ))}
              </div>
            )}

            {/* AI ANNOUNCEMENT BUILDER WIDGET */}
            <div className="rounded-3xl border border-slate-200 bg-slate-900 text-white p-8 shadow-md">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                
                <div className="space-y-4">
                  <div className="flex items-center gap-2">
                    <Sparkles className="h-5 w-5 text-school-yellow animate-spin" />
                    <span className="text-school-yellow text-xs font-black uppercase">เครื่องมือสนับสนุนแอดมิน</span>
                  </div>
                  <h3 className="text-xl sm:text-2xl font-black">AI ช่วยร่างคำทักทาย/หนังสือโรงเรียน</h3>
                  <p className="text-xs text-slate-300 leading-relaxed">
                    เหมาะสำหรับผู้อำนวยการ และคุณครูผู้ประสานงาน เพียงกรอกหัวข้อและข้อมูลสำคัญสั้นๆ 
                    ระบบ AI จะเรียบเรียงให้ออกมาเป็นประกาศสำนักทางการพร้อมอ้างอิงเบอร์โทรและลงท้ายผู้อำนวยการทันที!
                  </p>

                  <div className="space-y-3 pt-2 text-slate-900">
                    <div>
                      <label className="block text-white text-[11px] mb-1 font-bold">ชื่อเรื่องประกาศ</label>
                      <input 
                        type="text"
                        value={aiAnnounceTitle}
                        onChange={(e) => setAiAnnounceTitle(e.target.value)}
                        placeholder="เช่น ประชาสัมพันธ์สิทธิเรียนฟรีมีชุดแถม"
                        className="w-full rounded-xl bg-white px-3 py-2 text-xs focus:ring-1 focus:ring-school-yellow"
                      />
                    </div>
                    <div>
                      <label className="block text-white text-[11px] mb-1 font-bold">ใจความสำคัญ (ใส่คีย์เวิร์ดเรียงลำดับ)</label>
                      <textarea
                        rows={3}
                        value={aiAnnouncePoints}
                        onChange={(e) => setAiAnnouncePoints(e.target.value)}
                        placeholder="เช่น มารับสิทธิได้วันไหน, ต้องเตรียมเอกสารสำมะโนครัวอะไรมา, กำหนดลงชื่อไม่เกินเมื่อไหร่"
                        className="w-full rounded-xl bg-white px-3 py-2 text-xs focus:ring-1 focus:ring-school-yellow"
                      />
                    </div>
                    <button
                      onClick={handleAiGenerateAnnouncement}
                      disabled={generatingAnnounce}
                      className="bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black w-full py-2.5 rounded-xl transition flex items-center justify-center gap-1.5"
                    >
                      {generatingAnnounce ? "นับความสำคัญและเรียบเรียงคำ..." : "เขียนหนังสือราชการและประกาศให้อย่างไว"}
                    </button>
                  </div>
                </div>

                <div className="space-y-2 h-full flex flex-col justify-between">
                  <span className="text-[11px] text-amber-300 block font-bold">ผลร่างเอกสารอย่างเป็นทางการ:</span>
                  <div className="rounded-2xl bg-white/5 border border-white/15 p-5 flex-grow font-mono text-[11px] text-slate-200 leading-relaxed overflow-y-auto max-h-[300px] whitespace-pre-line">
                    {aiAnnounceResult || "ผลการร่างด้วย AI จะแสดงเมื่อใส่ข้อมูลครบถ้วน"}
                  </div>
                </div>

              </div>
            </div>

          </div>
        )}

        {/* TAB 3: TEACHERS DIRECTORY */}
        {activeTab === "teachers" && (
          <div className="space-y-8">
            <div className="bg-gradient-to-r from-school-red to-school-red-dark text-white rounded-3xl p-8 shadow">
              <h2 className="text-2xl sm:text-3xl font-black text-school-yellow">ทำเนียบผู้บริหาร และข้าราชการครู</h2>
              <p className="text-xs text-slate-200 mt-1 max-w-2xl leading-relaxed">
                โรงเรียนบ้านหนองหว้า นำทัพการศึกษายุคใหม่โดยผู้บริหารระดับมาตรฐานวิพากษ์และทำงานร่วมกับคณะครูประจำชั้น
                ซึ่งเรียงลำดับตามวิทยะฐานะ ความเชี่ยวชาญ และความตั้งใจอย่างอุตสาหะ
              </p>
            </div>

            {/* Filter controls */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
              <div className="relative w-full sm:w-80">
                <span className="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                  <Search className="h-4 w-4" />
                </span>
                <input 
                  type="text"
                  value={teacherSearch}
                  onChange={(e) => setTeacherSearch(e.target.value)}
                  placeholder="ค้นหาชื่อครู, ตำแหน่ง, สายสอน..."
                  className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none"
                />
              </div>

              <div className="flex gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                {["ทั้งหมด", "ผู้อำนวยการ", "คศ.3", "คศ.2", "ปฐมวัย", "พลศึกษา"].map((group) => (
                  <button
                    key={group}
                    onClick={() => setTeacherFilter(group)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 transition ${
                      teacherFilter === group ? 'bg-school-red text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    {group}
                  </button>
                ))}
              </div>
            </div>

            {/* Teacher Slate cards list */}
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {filteredTeachers.map((teach) => (
                <div 
                  key={teach.id}
                  className="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition text-center flex flex-col justify-between"
                >
                  <div className="h-4 bg-gradient-to-r from-school-red to-school-yellow"></div>
                  
                  <div className="p-6 space-y-4">
                    <div className="w-24 h-24 rounded-full overflow-hidden mx-auto border-2 border-slate-150 shadow-inner">
                      <img src={teach.imageUrl} alt={teach.name} className="w-full h-full object-cover" />
                    </div>

                    <div className="space-y-1">
                      <h4 className="font-bold text-sm text-slate-800">{teach.name}</h4>
                      <p className="text-[11px] text-slate-500 font-medium">{teach.position}</p>
                      <span className="inline-block bg-amber-100 text-amber-900 rounded-full text-[9px] font-black px-2.5 py-0.5">
                        {teach.level}
                      </span>
                    </div>

                    <p className="text-[10px] text-slate-400">
                      กลุ่มสาระ: <strong>{teach.subjectGroup || "ทั่วไป"}</strong>
                    </p>
                  </div>

                  {isAdminLoggedIn && (
                    <div className="border-t border-slate-100 p-3 bg-slate-50 flex items-center justify-center gap-2">
                      <button 
                        onClick={() => setupEditTeacher(teach)} 
                        className="p-1 rounded text-blue-600 hover:bg-blue-50"
                        title="แก้ไขประวัติครู"
                      >
                        <Edit2 className="h-3.5 w-3.5" />
                      </button>
                      <button 
                        onClick={() => deleteTeacher(teach.id)} 
                        className="p-1 rounded text-rose-600 hover:bg-rose-50"
                        title="ลบรายชื่อ"
                      >
                        <Trash2 className="h-3.5 w-3.5" />
                      </button>
                    </div>
                  )}

                  <div className="bg-slate-50 text-[10px] text-slate-400 py-1.5 font-heading">
                    ลำดับบุคลากร: {teach.order}
                  </div>
                </div>
              ))}
            </div>

          </div>
        )}

        {/* TAB 4: STUDENTS DIRECTORY */}
        {activeTab === "students" && (
          <div className="space-y-8">
            <div className="bg-gradient-to-r from-school-red to-school-red-dark text-white rounded-3xl p-8 shadow">
              <h2 className="text-2xl sm:text-3xl font-black text-school-yellow">ข้อมูลสถิตินักเรียนรายระดับชั้น</h2>
              <p className="text-xs text-slate-200 mt-1 max-w-2xl leading-relaxed">
                สมุดรายชื่อประวัตินักเรียนโรงเรียนบ้านหนองหน้องหว้า ใช้สำหรับตรวจสอบห้องเรียน ระดับชั้น 
                และความเคลื่อนไหวประชากรเด็กนักเรียน เพื่อประเมินคะแนนระบบอัตโนมัติความประพฤติ
              </p>
            </div>

            {/* Filter widgets */}
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
              <div className="relative w-full sm:w-80">
                <span className="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                  <Search className="h-4 w-4" />
                </span>
                <input 
                  type="text"
                  value={studentSearch}
                  onChange={(e) => setStudentSearch(e.target.value)}
                  placeholder="ค้นหารายชื่อเด็กชาย / เด็กหญิง..."
                  className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none"
                />
              </div>

              <div className="flex gap-2 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0">
                {["ทั้งหมด", "อนุบาล", "ประถมศึกษาปีที่ 1", "ประถมศึกษาปีที่ 5", "ประถมศึกษาปีที่ 6"].map((gr) => (
                  <button
                    key={gr}
                    onClick={() => setStudentGrade(gr)}
                    className={`px-3 py-1.5 rounded-lg text-xs font-bold shrink-0 transition ${
                      studentGrade === gr ? 'bg-school-red text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                    }`}
                  >
                    {gr}
                  </button>
                ))}
              </div>
            </div>

            {/* Student grid dynamic lists */}
            <div className="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left font-sans text-xs border-collapse">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-100 text-slate-500">
                      <th className="p-4 font-bold">ชื่อ - สกุลนักเรียน</th>
                      <th className="p-4 font-bold">ระดับชั้นเรียน</th>
                      <th className="p-4 font-bold">กลุ่มชั้นเรียนย่อย</th>
                      <th className="p-4 font-bold">เพศ</th>
                      <th className="p-4 font-bold">สถานะความประพฤติ</th>
                      {isAdminLoggedIn && <th className="p-4 font-bold text-center">จัดการ</th>}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {filteredStudents.map((stud) => (
                      <tr key={stud.id} className="hover:bg-slate-50/50 transition">
                        <td className="p-4 font-bold text-slate-800">{stud.name}</td>
                        <td className="p-4">
                          <span className="bg-sky-100 text-sky-800 px-2 py-0.5 rounded-full font-semibold">
                            {stud.grade}
                          </span>
                        </td>
                        <td className="p-4 text-slate-500">ห้อง {stud.classroom}</td>
                        <td className="p-4 text-slate-500">{stud.gender}</td>
                        <td className="p-4">
                          <span className="bg-emerald-100 text-emerald-800 font-semibold rounded px-2 py-0.5">
                            ผ่านการทดสอบ
                          </span>
                        </td>
                        {isAdminLoggedIn && (
                          <td className="p-4">
                            <div className="flex items-center justify-center gap-1.5">
                              <button onClick={() => setupEditStudent(stud)} className="text-blue-600 px-1 hover:underline">แก้ไข</button>
                              <span className="text-slate-300">|</span>
                              <button onClick={() => deleteStudent(stud.id)} className="text-rose-600 px-1 hover:underline">ลบ</button>
                            </div>
                          </td>
                        )}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        )}

        {/* TAB 5: DOWNLOAD DOCUMENTS ROOM */}
        {activeTab === "downloads" && (
          <div className="space-y-8">
            <div className="bg-gradient-to-r from-school-red to-school-red-dark text-white rounded-3xl p-8 shadow">
              <h2 className="text-2xl sm:text-3xl font-black text-school-yellow">ศูนย์คลังแผนงานและเอกสารจัดซื้อจัดจ้าง</h2>
              <p className="text-xs text-slate-100 leading-relaxed mt-1">
                เผยแพร่แผนพัฒนาคุณภาพ ใบอนุญาต ข้อตกลง PA และแบบฟอร์มโรงเรียนบ้านหนองหว้า โดยตรงอย่างเปิดเผยโปร่งใส
              </p>
            </div>

            {/* SEARCH AND AI ADVICE */}
            <div className="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-4">
              <h4 className="font-bold text-sm text-slate-700 flex items-center gap-1">
                <Sparkles className="h-4 w-4 text-school-yellow" />
                AI ตัวกรองอัจฉริยะช่วยค้นเอกสาร:
              </h4>

              <div className="flex flex-col sm:flex-row gap-3">
                <input 
                  type="text" 
                  value={docSearch}
                  onChange={(e) => {
                    setDocSearch(e.target.value);
                    handleAiDocSearch(e.target.value);
                  }}
                  className="flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-xs focus:ring-1 focus:ring-school-red"
                  placeholder="พิมพ์คีย์เวิร์ดเพื่อค้นหา เช่น 'ใบสมัคร', 'รายงาน SAR', 'รายงานแผนพัฒนา'"
                />
                
                <select 
                  value={docCategory}
                  onChange={(e) => setDocCategory(e.target.value)}
                  className="rounded-xl border border-slate-200 px-3 py-2 text-xs focus:ring-1 bg-white"
                >
                  <option value="ทั้งหมด">แสดงทุกหมวดหมู่</option>
                  <option value="เอกสารทั่วไป">เอกสารทั่วไป / ใบสมัคร</option>
                  <option value="แผนงานและนโยบาย">แผนงานและนโยบาย</option>
                  <option value="ประกันคุณภาพ">ประกันคุณภาพ</option>
                  <option value="เอกสารครู">แบบฟอร์มครู / PA</option>
                </select>
              </div>

              {aiDocLoader ? (
                <p className="text-xs text-slate-400 animate-pulse">น้องหว้า AI กำลังจัดส่งคำพูดแนะแนวทางเรื่องเอกสาร...</p>
              ) : aiDocAdvice ? (
                <div className="bg-slate-50 border border-slate-250 p-4 rounded-2xl text-xs flex items-start gap-2">
                  <Bot className="h-5 w-5 text-school-red shrink-0" />
                  <p className="text-slate-600 italic leading-relaxed">{aiDocAdvice}</p>
                </div>
              ) : null}
            </div>

            {/* Document display list */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {filteredDocs.map((doc) => (
                <div 
                  key={doc.id}
                  className="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm hover:shadow-md transition flex items-center justify-between gap-4"
                >
                  <div className="space-y-1.5">
                    <span className="inline-block bg-school-red/10 text-school-red rounded text-[9px] font-black px-2 py-0.5">
                      {doc.category}
                    </span>
                    <h4 className="font-bold text-sm text-slate-800 leading-snug">
                      {doc.title}
                    </h4>
                    <div className="flex items-center gap-3 text-[10px] text-slate-400">
                      <span>ชนิดไฟล์: <strong>{doc.fileType}</strong></span>
                      <span>ขนาด: {doc.fileSize}</span>
                      <span>ดาวน์โหลด: {doc.downloadCount} ครั้ง</span>
                    </div>
                  </div>

                  <div className="flex flex-col items-end gap-2 text-right shrink-0">
                    <button
                      onClick={() => handleDownloadDoc(doc.id, "https://www.orimi.com/pdf-test.pdf")}
                      className="bg-school-yellow hover:bg-amber-400 text-slate-900 rounded-xl px-4 py-2.5 text-xs font-black shadow-sm flex items-center gap-1.5 transition"
                    >
                      <Download className="h-3.5 w-3.5" />
                      ดาวน์โหลด
                    </button>
                    {isAdminLoggedIn && (
                      <button 
                        onClick={() => deleteDoc(doc.id)} 
                        className="text-[10px] text-rose-600 hover:underline"
                      >
                        ลบออก
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>

          </div>
        )}

        {/* TAB 6: GALLERIES DIRECTORY */}
        {activeTab === "galleries" && (
          <div className="space-y-8">
            <div className="bg-gradient-to-r from-school-red to-school-red-dark text-white rounded-3xl p-8 shadow">
              <h2 className="text-2xl sm:text-3xl font-black text-school-yellow">อัลบั้มภาพแกลเลอรีโรงเรียนบ้านหนองหว้า</h2>
              <p className="text-xs text-slate-100 leading-relaxed mt-1">
                บันทึกประวัติศาสตร์การพัฒนาเยาวชน นิทรรศการผลงานสภานักเรียน กิจกรรมวันสำคัญ และความภาคภูมิใจร่วมกับชุมชนคนบุรีรัมย์
              </p>
            </div>

            {galleries.map((album) => (
              <div key={album.id} className="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                <div className="border-b border-slate-100 pb-4">
                  <div className="flex items-center justify-between">
                    <h3 className="font-bold text-lg text-slate-800">{album.title}</h3>
                    <span className="text-xs text-slate-400">วันที่สร้าง: {album.date}</span>
                  </div>
                  <p className="text-xs text-slate-500 mt-1">{album.description}</p>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                  {album.images.map((img, i) => (
                    <div 
                      key={i}
                      onClick={() => setLightboxImage(img)}
                      className="relative rounded-2xl overflow-hidden aspect-video bg-slate-100 cursor-pointer group border border-slate-100"
                    >
                      <img src={img} alt="" className="w-full h-full object-cover group-hover:scale-105 transition" />
                      <div className="absolute inset-0 bg-slate-900/10 group-hover:bg-slate-950/0 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <span className="text-[10px] bg-slate-900/80 text-white font-bold px-2 px-2.5 py-1 rounded-full">ดูรูปใหญ่</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        )}

        {/* TAB 7: ADMIN CONTROLS CENTER */}
        {activeTab === "admin" && (
          <div className="space-y-8">
            
            {/* Auth card if not logged in */}
            {!isAdminLoggedIn ? (
              <div className="max-w-md mx-auto bg-white rounded-3xl border border-slate-150 p-8 shadow-xl relative overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-2.5 bg-school-red"></div>
                <div className="text-center space-y-2 mb-6">
                  <span className="inline-block p-3 bg-school-red/10 rounded-full text-school-red">
                    <Lock className="h-6 w-6" />
                  </span>
                  <h3 className="text-xl font-bold text-slate-800">ระบบแอดมินสำหรับจำลองฐานข้อมูล MySQL</h3>
                  <p className="text-xs text-slate-500">
                    เข้าสู่โหมดปรับแต่ง ครู, นักเรียน, ไฟล์เอกสาร และการตั้งค่า เพื่อควบคุม SQL ตารางแบบเรียลไทม์
                  </p>
                </div>

                <form onSubmit={handleAdminLogin} className="space-y-4 text-xs font-sans">
                  {authError && (
                    <div className="bg-rose-50 border border-rose-200 text-rose-700 p-3.5 rounded-xl flex items-start gap-2">
                      <AlertTriangle className="h-4 w-4 shrink-0" />
                      <span>{authError}</span>
                    </div>
                  )}

                  <div className="space-y-1">
                    <label className="block text-slate-600 font-bold">ชื่อผู้ใช้ระบบ (Username)</label>
                    <input 
                      type="text"
                      required
                      value={loginUsername}
                      onChange={(e) => setLoginUsername(e.target.value)}
                      placeholder="ป้อนผู้ใช้ระบบ 'admin'"
                      className="w-full rounded-xl border border-slate-200 p-3 text-xs focus:ring-1 focus:ring-school-red focus:outline-none"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="block text-slate-600 font-bold">รหัสผ่านหลังบ้าน (Password)</label>
                    <input 
                      type="password"
                      required
                      value={loginPassword}
                      onChange={(e) => setLoginPassword(e.target.value)}
                      placeholder="ป้อนรหัสแอดมิน 'admin123'"
                      className="w-full rounded-xl border border-slate-200 p-3 text-xs focus:ring-1 focus:ring-school-red focus:outline-none"
                    />
                  </div>

                  <button 
                    type="submit"
                    className="w-full bg-school-red hover:bg-school-red-dark text-white font-black py-3 rounded-xl transition duration-150 shadow-md flex items-center justify-center gap-1.5"
                  >
                    <span>ลงชื่อเข้าสู่แดชบอร์ด</span>
                  </button>
                </form>

                <div className="mt-6 pt-4 border-t border-slate-100 text-[10px] text-slate-400 text-center leading-relaxed">
                  รหัสผ่านชุดนี้จำลองการแฮชด้วย BCRYPT ความปลอดภัยระดับรัฐธรรมนูญ พาสเวิร์ดตั้งต้นคือ <strong>admin123</strong>
                </div>
              </div>
            ) : (
              // FULL ADMIN CONTROL PANEL ACTIVE (DASHBOARD)
              <div className="space-y-8">
                
                {/* Intro panel */}
                <div className="bg-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-6">
                  <div className="space-y-2">
                    <div className="flex items-center gap-2">
                      <span className="bg-amber-400 text-slate-950 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase">
                        ADMIN PANEL
                      </span>
                      <span className="text-emerald-400 text-xs">● เชื่อมโยง JSON Database สำเร็จ</span>
                    </div>
                    <h2 className="text-2xl font-black text-white">ยินดีต้อนรับแอดมิน โรงเรียนบ้านหนองหว้า</h2>
                    <p className="text-xs text-slate-300">
                      คุณสามารถทำภารกิจจำลอง เพิ่ม ลบ แก้ไข ข้อมูลครู นักเรียน แผงเอกสาร หรือความประพฤติ จากนั้นจำลองสเปคเพื่อก็อปปี้สคริปต์ SQL นำไปใช้จริงได้ทันที!
                    </p>
                  </div>
                  
                  <button 
                    onClick={handleAdminLogout}
                    className="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold shrink-0 transition"
                  >
                    ออกจากระบบแอดมิน
                  </button>
                </div>

                {/* Dashboard stats widgets */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="bg-white border border-slate-150 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                      <span className="text-[10px] text-slate-400 block font-bold">ข่าวประชาสัมพันธ์</span>
                      <strong className="text-2xl font-black text-slate-800">{news.length} ชิ้น</strong>
                    </div>
                    <span className="p-2.5 bg-rose-50 rounded-xl text-school-red">
                      <Newspaper className="h-5 w-5" />
                    </span>
                  </div>
                  <div className="bg-white border border-slate-150 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                      <span className="text-[10px] text-slate-400 block font-bold">ทำเนียบบุคลากร</span>
                      <strong className="text-2xl font-black text-slate-800">{teachers.length} ท่าน</strong>
                    </div>
                    <span className="p-2.5 bg-amber-50 rounded-xl text-amber-600">
                      <Users className="h-5 w-5" />
                    </span>
                  </div>
                  <div className="bg-white border border-slate-150 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                      <span className="text-[10px] text-slate-400 block font-bold">นักเรียนในระบบ</span>
                      <strong className="text-2xl font-black text-slate-800">{students.length} รายชื่อ</strong>
                    </div>
                    <span className="p-2.5 bg-blue-50 rounded-xl text-blue-600">
                      <GraduationCap className="h-5 w-5" />
                    </span>
                  </div>
                  <div className="bg-white border border-slate-150 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                    <div>
                      <span className="text-[10px] text-slate-400 block font-bold">เอกสารราชการเผยแพร่</span>
                      <strong className="text-2xl font-black text-slate-800">{downloads.length} ฉบับ</strong>
                    </div>
                    <span className="p-2.5 bg-emerald-50 rounded-xl text-emerald-600">
                      <FileDown className="h-5 w-5" />
                    </span>
                  </div>
                </div>

                {/* Quick CRUD triggers with Modals/forms */}
                <div className="bg-white border border-slate-150 rounded-3xl p-6 shadow-sm space-y-6">
                  <div className="border-b border-slate-100 pb-4">
                    <h3 className="text-base font-bold text-slate-800">เครื่องมือจัดการเนื้อหาความปลอดภัยสูง</h3>
                    <p className="text-xs text-slate-500">กรุณาเลือกตารางที่คุณต้องการ เพิ่ม หรือ แก้ไข ข้อมูลจำลอง</p>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <button 
                      onClick={() => { setEditorTarget('settings'); setSelectedItemEditId(null); }}
                      className="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow flex items-center gap-1.5"
                    >
                      <SettingsIcon className="h-4 w-4 text-school-yellow" />
                      ตั้งค่าข้อมูลจำลองโรงเรียน
                    </button>
                    <button 
                      onClick={() => { setEditorTarget('news'); setSelectedItemEditId(null); }}
                      className="px-4 py-2 bg-school-red text-white rounded-xl text-xs font-bold hover:bg-school-red-dark transition shadow flex items-center gap-1.5"
                    >
                      <Plus className="h-4 w-4" />
                      เพิ่มข่าวสารโรงเรียน
                    </button>
                    <button 
                      onClick={() => { setEditorTarget('teachers'); setSelectedItemEditId(null); }}
                      className="px-4 py-2 bg-amber-500 text-slate-900 rounded-xl text-xs font-bold hover:bg-amber-400 transition shadow flex items-center gap-1.5"
                    >
                      <Plus className="h-4 w-4" />
                      เพิ่มทำเนียบครู
                    </button>
                    <button 
                      onClick={() => { setEditorTarget('students'); setSelectedItemEditId(null); }}
                      className="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition shadow flex items-center gap-1.5"
                    >
                      <Plus className="h-4 w-4" />
                      เพิ่มประวัตินักเรียน
                    </button>
                    <button 
                      onClick={() => { setEditorTarget('downloads'); setSelectedItemEditId(null); }}
                      className="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition shadow flex items-center gap-1.5"
                    >
                      <Plus className="h-4 w-4" />
                      เพิ่มไฟล์เอกสารดาวน์โหลด
                    </button>
                  </div>

                  {/* FORM RENDER CONTAINER IF ACTIVE */}
                  {editorTarget && (
                    <div className="mt-6 border border-slate-200/80 rounded-2xl p-6 bg-slate-50/50 space-y-6 relative">
                      <button 
                        onClick={() => { setEditorTarget(null); setSelectedItemEditId(null); }}
                        className="absolute top-4 right-4 text-slate-400 hover:text-slate-800"
                      >
                        <X className="h-5 w-5" />
                      </button>

                      <div className="border-b border-slate-200 pb-3">
                        <h4 className="font-bold text-sm text-school-red-dark flex items-center gap-1">
                          <Edit2 className="h-4 w-4 animate-spin text-school-yellow-dark" />
                          <span>กล่องฟอร์มกรอกข้อมูล: หมวดหมู่ {editorTarget.toUpperCase()}</span>
                        </h4>
                        <p className="text-[10px] text-slate-400">กรอกข้อมูลให้ครบถ้วนเพื่อทำการบันทึกลงจำแลงฐานข้อมูล MySQL</p>
                      </div>

                      {/* 1. News form editor */}
                      {editorTarget === 'news' && (
                        <form onSubmit={saveNewsForm} className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">หัวข้อข่าวประชาสัมพันธ์</label>
                            <input 
                              type="text"
                              value={newsFormTitle}
                              onChange={(e) => setNewsFormTitle(e.target.value)}
                              placeholder="เช่น ประกาศทำหมันตู้กดน้ำ หรือ ค่ายภาษาอังกฤษ"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">หมวดหมู่ข่าว</label>
                            <select 
                              value={newsFormCategory}
                              onChange={(e) => setNewsFormCategory(e.target.value)}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            >
                              <option value="ข่าวประชาสัมพันธ์ทั่วไป">ข่าวประชาสัมพันธ์ทั่วไป</option>
                              <option value="ข่าวกิจกรรม">ข่าวกิจกรรม</option>
                              <option value="ประชุมและวิชาการ">ประชุมและวิชาการ</option>
                            </select>
                          </div>
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">เนื้อหาข่าวฉบับเต็ม</label>
                            <textarea 
                              rows={5}
                              value={newsFormContent}
                              onChange={(e) => setNewsFormContent(e.target.value)}
                              placeholder="ใส่ข้อมูลรายละเอียด เพื่อแสดงเนื้อข่าวฉบับสมบูรณ์"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">ลิงก์รูปภาพตัวอย่าง (ImageUrl)</label>
                            <input 
                              type="text"
                              value={newsFormImage}
                              onChange={(e) => setNewsFormImage(e.target.value)}
                              placeholder="https://images.unsplash.com/..."
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>

                          <div className="md:col-span-2 flex justify-end gap-2">
                            <button 
                              type="button" 
                              onClick={() => setEditorTarget(null)}
                              className="px-4 py-2 bg-slate-200 rounded-xl text-slate-700"
                            >
                              ยกเลิก
                            </button>
                            <button 
                              type="submit"
                              className="px-5 py-2 bg-school-red hover:bg-school-red-dark text-white rounded-xl font-bold"
                            >
                              บันทึกข่าวด้านบน
                            </button>
                          </div>
                        </form>
                      )}

                      {/* 2. Teacher fields */}
                      {editorTarget === 'teachers' && (
                        <form onSubmit={saveTeacherForm} className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">ชื่อ-นามสกุล ครู (พานยศ คำนำหน้า)</label>
                            <input 
                              type="text"
                              value={teacherFormName}
                              onChange={(e) => setTeacherFormName(e.target.value)}
                              placeholder="เช่น นางสาวปราณี สอนดี"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">ตำแหน่ง / วิทยะฐานะ</label>
                            <input 
                              type="text"
                              value={teacherFormPosition}
                              onChange={(e) => setTeacherFormPosition(e.target.value)}
                              placeholder="เช่น ครูวิทยะฐานะชำนาญการพิเศษ"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">กลุ่มระดับชั้น / วิยานาม</label>
                            <select 
                              value={teacherFormLevel}
                              onChange={(e) => setTeacherFormLevel(e.target.value)}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            >
                              <option value="ผู้อำนวยการโรงเรียน (คศ.3)">ผู้อำนวยการโรงเรียน (คศ.3)</option>
                              <option value="ครูชำนาญการพิเศษ (คศ.3)">ครูชำนาญการพิเศษ (คศ.3)</option>
                              <option value="ครูชำนาญการ (คศ.2)">ครูชำนาญการ (คศ.2)</option>
                              <option value="ครูผู้ช่วย">ครูผู้ช่วย</option>
                              <option value="บุคลากรทางการศึกษา">บุคลากรทางการศึกษา</option>
                            </select>
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">กลุ่มสาระการเรียนรู้</label>
                            <input 
                              type="text"
                              value={teacherFormSubject}
                              onChange={(e) => setTeacherFormSubject(e.target.value)}
                              placeholder="เช่น ภาษาไทย / คณิตศาสตร์ / วิทยาศาสตร์"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">ลิงก์ประวัติอวาตาร์ภาพ (ImageUrl)</label>
                            <input 
                              type="text"
                              value={teacherFormImage}
                              onChange={(e) => setTeacherFormImage(e.target.value)}
                              placeholder="https://images.unsplash.com/..."
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>

                          <div className="md:col-span-2 flex justify-end gap-2">
                            <button 
                              type="button" 
                              onClick={() => setEditorTarget(null)}
                              className="px-4 py-2 bg-slate-200 rounded-xl"
                            >
                              ปิด
                            </button>
                            <button 
                              type="submit"
                              className="px-5 py-2 bg-amber-500 hover:bg-amber-400 text-slate-900 rounded-xl font-bold"
                            >
                              ตกลงบันทึก
                            </button>
                          </div>
                        </form>
                      )}

                      {/* 3. Students editor */}
                      {editorTarget === 'students' && (
                        <form onSubmit={saveStudentForm} className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">ชื่อเด็กหญิง หรือเด็กชาย</label>
                            <input 
                              type="text"
                              value={studentFormName}
                              onChange={(e) => setStudentFormName(e.target.value)}
                              placeholder="ป้อน ชื่อ-สกุลนักเรียน"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">ระดับชั้นเรียน</label>
                            <select 
                              value={studentFormGrade}
                              onChange={(e) => setStudentFormGrade(e.target.value)}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            >
                              <option value="อนุบาล 2">อนุบาล 2</option>
                              <option value="อนุบาล 3">อนุบาล 3</option>
                              <option value="ประถมศึกษาปีที่ 1">ประถมศึกษาปีที่ 1</option>
                              <option value="ประถมศึกษาปีที่ 2">ประถมศึกษาปีที่ 2</option>
                              <option value="ประถมศึกษาปีที่ 3">ประถมศึกษาปีที่ 3</option>
                              <option value="ประถมศึกษาปีที่ 4">ประถมศึกษาปีที่ 4</option>
                              <option value="ประถมศึกษาปีที่ 5">ประถมศึกษาปีที่ 5</option>
                              <option value="ประถมศึกษาปีที่ 6">ประถมศึกษาปีที่ 6</option>
                            </select>
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">ห้องเรียนย่อย</label>
                            <input 
                              type="text"
                              value={studentFormClass}
                              onChange={(e) => setStudentFormClass(e.target.value)}
                              placeholder="เช่น 1/1 หรือ 6/1"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">เพศสภาพ</label>
                            <select 
                              value={studentFormGender}
                              onChange={(e) => setStudentFormGender(e.target.value)}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            >
                              <option value="ชาย">ชาย</option>
                              <option value="หญิง">หญิง</option>
                            </select>
                          </div>

                          <div className="md:col-span-2 flex justify-end gap-2">
                            <button 
                              type="button" 
                              onClick={() => setEditorTarget(null)}
                              className="px-4 py-2 bg-slate-200 rounded-xl"
                            >
                              ยกเลิก
                            </button>
                            <button 
                              type="submit"
                              className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold"
                            >
                              บันทึกนักเรียน
                            </button>
                          </div>
                        </form>
                      )}

                      {/* 4. Downloads Documents */}
                      {editorTarget === 'downloads' && (
                        <form onSubmit={saveDocForm} className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">ชื่อหัวเรื่องเอกสารทางการราชการ</label>
                            <input 
                              type="text"
                              value={docFormTitle}
                              onChange={(e) => setDocFormTitle(e.target.value)}
                              placeholder="เช่น แผนปฏิบัติการประจำปีงบประมาณ 2569"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">กลุ่มเอกสาร</label>
                            <select 
                              value={docFormCategory}
                              onChange={(e) => setDocFormCategory(e.target.value)}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            >
                              <option value="เอกสารทั่วไป">เอกสารทั่วไป</option>
                              <option value="แผนงานและนโยบาย">แผนงานและนโยบาย</option>
                              <option value="ประกันคุณภาพ">ประกันคุณภาพ</option>
                              <option value="เอกสารครู">เอกสารครู</option>
                            </select>
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">นามสกุลไฟล์</label>
                            <select 
                              value={docFormType}
                              onChange={(e) => setDocFormType(e.target.value)}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            >
                              <option value="PDF">PDF Document</option>
                              <option value="WORD">Microsoft Word Document</option>
                            </select>
                          </div>
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">ระบุขนาดจำลอง</label>
                            <input 
                              type="text"
                              value={docFormSize}
                              onChange={(e) => setDocFormSize(e.target.value)}
                              placeholder="เช่น 1.2 MB หรือ 450 KB"
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>

                          <div className="md:col-span-2 flex justify-end gap-2">
                            <button 
                              type="button" 
                              onClick={() => setEditorTarget(null)}
                              className="px-4 py-2 bg-slate-200 rounded-xl"
                            >
                              ยกเลิก
                            </button>
                            <button 
                              type="submit"
                              className="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold"
                            >
                              อัปโหลดเอกสารเสร็จสิ้น
                            </button>
                          </div>
                        </form>
                      )}

                      {/* 5. Settings general editor */}
                      {editorTarget === 'settings' && settingsForm && (
                        <form onSubmit={saveSettings} className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">ชื่อโรงเรียนปหลัก</label>
                            <input 
                              type="text"
                              value={settingsForm.schoolName}
                              onChange={(e) => setSettingsForm({ ...settingsForm, schoolName: e.target.value })}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">คำสั้นย่อเรียกชื่อสถานศึกษา</label>
                            <input 
                              type="text"
                              value={settingsForm.shortName}
                              onChange={(e) => setSettingsForm({ ...settingsForm, shortName: e.target.value })}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">เบอร์โทรติดต่อหลัก</label>
                            <input 
                              type="text"
                              value={settingsForm.phone}
                              onChange={(e) => setSettingsForm({ ...settingsForm, phone: e.target.value })}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1">
                            <label className="font-bold text-slate-600">อีเมลทางการโรงเรียน</label>
                            <input 
                              type="email"
                              value={settingsForm.email}
                              onChange={(e) => setSettingsForm({ ...settingsForm, email: e.target.value })}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">หน่วยหน่วยงานต้นสังกัด</label>
                            <input 
                              type="text"
                              value={settingsForm.jurisdiction}
                              onChange={(e) => setSettingsForm({ ...settingsForm, jurisdiction: e.target.value })}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          <div className="space-y-1 md:col-span-2">
                            <label className="font-bold text-slate-600">ที่ตั้งอาคารโรงเรียนจริง</label>
                            <input 
                              type="text"
                              value={settingsForm.address}
                              onChange={(e) => setSettingsForm({ ...settingsForm, address: e.target.value })}
                              className="w-full rounded-xl border p-2.5 bg-white text-xs"
                            />
                          </div>
                          
                          <div className="md:col-span-2 flex justify-end gap-2">
                            <button 
                              type="button" 
                              onClick={() => setEditorTarget(null)}
                              className="px-4 py-2 bg-slate-200 rounded-xl"
                            >
                              ปิด
                            </button>
                            <button 
                              type="submit"
                              className="px-5 py-2 bg-slate-900 text-white rounded-xl font-bold"
                            >
                              อัปเดตข้อมูลโครงสร้างหน่วยงาน
                            </button>
                          </div>
                        </form>
                      )}

                    </div>
                  )}

                </div>

              </div>
            )}

          </div>
        )}

        {/* TAB 8: DEVELOPER ARTIFACT & SQL EXPORTER */}
        {activeTab === "developer" && (
          <DeveloperCenter />
        )}

      </main>

      {/* LIGHTBOX MODAL FOR IMAGES */}
      {lightboxImage && (
        <div 
          onClick={() => setLightboxImage(null)}
          className="fixed inset-0 z-50 bg-slate-950/90 flex items-center justify-center p-4 cursor-zoom-out"
        >
          <div className="relative max-w-4xl max-h-[85vh] overflow-hidden rounded-3xl bg-white shadow-2xl p-2">
            <button 
              onClick={() => setLightboxImage(null)}
              className="absolute top-4 right-4 text-white bg-slate-900/80 hover:bg-slate-950 h-8 w-8 rounded-full flex items-center justify-center"
            >
              <X className="h-4 w-4" />
            </button>
            <img src={lightboxImage} alt="ขยายรูปแกลเลอรีโรงเรียนบ้านหนองหว้า" className="max-w-full max-h-[80vh] object-contain rounded-2xl" />
          </div>
        </div>
      )}

      {/* FOOTER AREA (Vibrant design standard footer with comprehensive details) */}
      <footer className="bg-slate-900 text-slate-400 font-sans border-t-4 border-school-pink pt-12 pb-6 mt-16 text-xs">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
          
          <div className="space-y-4">
            <div className="flex items-center gap-2">
              <div className="h-8 w-8 rounded-full bg-white flex items-center justify-center text-school-pink font-black">
                นห
              </div>
              <h4 className="font-bold text-white text-base leading-none">{settings.schoolName}</h4>
            </div>
            <p className="leading-relaxed text-slate-300 font-heading">
              ระดับชั้นอนุบาล 2 ถึง ชั้นประถมศึกษาปีที่ 6 โรงเรียนรัฐบาลภายใต้การดูแลมุ่งเสริมนวัตกรรมสิ่งแวดล้อม
            </p>
            <div className="flex gap-3 text-white text-xs">
              <span className="bg-school-pink text-white px-2.5 py-1 rounded-full font-bold">ชมพู-ขาว ก้าวไกลวิชาการ</span>
            </div>
          </div>

          <div className="space-y-3">
            <h5 className="font-bold text-base text-white">ช่องทางการติดต่อด่วน</h5>
            <div className="space-y-2 leading-relaxed">
              <p>📍 {settings.address}</p>
              <p>📞 โทรศัพท์ติดต่ออาคารอำนวยการ: {settings.phone}</p>
              <p>✉️ อีเมลกลาง: {settings.email}</p>
            </div>
          </div>

          <div className="space-y-3">
            <h5 className="font-bold text-base text-white">หน่วยงานร่วมสนับสนุน</h5>
            <div className="space-y-1.5 leading-relaxed">
              <p>• {settings.jurisdiction}</p>
              <p>• สำนักงานคณะกรรมการการศึกษาขั้นพื้นฐาน (สพฐ.)</p>
              <p>• กระทรวงศึกษาธิการ ประเทศไทย</p>
              <p>• คณะกรรมการการประเมินสถานศึกษาคุณธรรมเด่น</p>
            </div>
          </div>

          <div className="space-y-4">
            <h5 className="font-bold text-base text-white">น้องหว้า AI และความปลอดภัย</h5>
            <p className="leading-relaxed">
              สแกนตรวจสอบเอกสารและข้อความตอบคำถามอัตโนมัติ ด้วยระบบปัญญาประดิษฐ์ออฟไลน์และระบบคัดกรองคำศัพท์หยาบโลน 24 ชั่วโมง
            </p>
            <div className="p-3 bg-white/5 rounded-2xl border border-white/10 flex items-center gap-2 text-emerald-400">
              <span className="h-2.5 w-2.5 bg-emerald-500 rounded-full"></span>
              <span>ระบบจัดเก็บความปลอดภัยถูกต้อง</span>
            </div>
          </div>

        </div>

        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 border-t border-slate-800 text-center text-slate-505 flex flex-col sm:flex-row justify-between items-center gap-3">
          <p>© 2026 {settings.schoolName} (Ban Nong Wa School). สงวนลิขสิทธิ์ทั้งหมด ข้อมูลจำเรียงเพื่อสนับสนุนการพัฒนาเชิงพาณิชย์และโปรแกรมเมอร์</p>
          <div className="flex gap-4">
            <span className="text-slate-500">มาตรฐานการเข้าถึง WCAG 2.1</span>
            <span>|</span>
            <span className="text-slate-500">นโยบายคุกกี้ระบบ</span>
          </div>
        </div>
      </footer>

    </div>
  );
}
