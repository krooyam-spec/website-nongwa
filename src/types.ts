export interface SchoolSettings {
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

export interface News {
  id: string;
  title: string;
  category: string;
  content: string;
  summary?: string;
  imageUrl: string;
  date: string;
  views: number;
}

export interface Teacher {
  id: string;
  name: string;
  position: string;
  level: string;
  subjectGroup?: string;
  imageUrl: string;
  order: number;
}

export interface Student {
  id: string;
  name: string;
  grade: string;
  classroom: string;
  gender: string;
}

export interface DownloadDoc {
  id: string;
  title: string;
  category: string;
  fileType: string;
  fileSize: string;
  downloadCount: number;
  date: string;
}

export interface GalleryAlbum {
  id: string;
  title: string;
  description: string;
  coverImage: string;
  images: string[];
  date: string;
}

export interface Banner {
  id: string;
  title: string;
  subtitle: string;
  imageUrl: string;
  active: boolean;
}

export interface User {
  id: string;
  username: string;
  role: string;
  name: string;
}

export interface DatabaseState {
  users: User[];
  news: News[];
  teachers: Teacher[];
  students: Student[];
  downloads: DownloadDoc[];
  galleries: GalleryAlbum[];
  banners: Banner[];
  settings: SchoolSettings;
}
