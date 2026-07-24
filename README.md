# Smart Member & Booking System Haircut

ระบบสมาชิกและจองคิวสำหรับร้านตัดผม โดยสมาชิกไม่ต้องมีบัญชีผู้ใช้หรือรหัสผ่าน ลูกค้าใช้หมายเลขโทรศัพท์เพื่อค้นหาข้อมูล และระบบจะสร้าง Temporary Member Session ที่มีอายุ 15 นาทีผ่าน HttpOnly Cookie

## เทคโนโลยีที่ใช้

- Backend: PHP 8.3, Laravel 12
- Frontend: HTML5, Tailwind CSS 4, TypeScript, Vite
- Database: MySQL 8
- รูปแบบระบบ: Laravel MVC พร้อม Service Layer และเตรียมโครงสร้างสำหรับ REST API

## ความต้องการของระบบ

ก่อนเริ่มติดตั้ง กรุณาตรวจสอบว่ามีโปรแกรมต่อไปนี้

- PHP 8.3 หรือใหม่กว่า พร้อม extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- Composer 2
- Node.js 20 หรือใหม่กว่า และ npm
- MySQL 8

ตรวจสอบเวอร์ชันด้วยคำสั่ง:

```bash
php -v
composer --version
node -v
npm -v
mysql --version
```

## ขั้นตอนติดตั้ง

### 1. ติดตั้ง PHP 8.3 และ Composer (กรณีใช้ XAMPP รุ่นเก่า)

XAMPP เดิมในเครื่องนี้ใช้ PHP 7.3.2 ซึ่งไม่รองรับ Laravel 12 และ XAMPP สำหรับ Windows ไม่มี PHP 8.3 อย่างเป็นทางการ จึงมีสคริปต์สำหรับติดตั้ง PHP 8.3 แบบแยกใน `tools\php83` โดยไม่แก้ไข XAMPP เดิม:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\scripts\install-php83.ps1
```

สคริปต์ดึงไฟล์จาก PHP for Windows archive โดยตรง จึงไม่ขึ้นกับ URL ของรุ่นล่าสุดที่อาจเปลี่ยนเมื่อมี PHP รุ่นใหม่ออกมา

หลังติดตั้ง ใช้ `tools\composer.bat` แทนคำสั่ง `composer` และใช้ `scripts\laravel.ps1` แทน `php artisan` ตัวอย่างเช่น ` .\scripts\laravel.ps1 migrate`.

### 2. ติดตั้ง Dependencies

เปิด Terminal ในโฟลเดอร์โปรเจกต์ แล้วรัน:

```bash
tools\composer.bat install
npm install
```

### 3. ตั้งค่า Environment

คัดลอกไฟล์ตัวอย่างและสร้าง Application Key:

```bash
cp .env.example .env
.\scripts\laravel.ps1 key:generate
```

สำหรับ Windows PowerShell ใช้:

```powershell
Copy-Item .env.example .env
.\scripts\laravel.ps1 key:generate
```

แก้ไขค่าเชื่อมต่อฐานข้อมูลในไฟล์ `.env` ให้ตรงกับเครื่องของคุณ:

```env
APP_NAME="Smart Cut"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smhairde_db
DB_USERNAME=root
DB_PASSWORD=
```

### 4. สร้างฐานข้อมูล

เข้าสู่ MySQL แล้วสร้างฐานข้อมูลด้วย UTF-8:

```sql
CREATE DATABASE smhairde_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

### 5. สร้างตารางและข้อมูล Role เริ่มต้น

```bash
.\scripts\laravel.ps1 migrate --seed
```

คำสั่งนี้จะสร้างตารางหลักทั้งหมด รวมถึง Role เริ่มต้น: Owner, Manager, Reception, Cashier และ Marketing

> ข้อสำคัญ: หลังจากรัน migration เริ่มต้นแล้ว ห้ามแก้ไขไฟล์ migration เดิม หากต้องการเพิ่มโครงสร้างฐานข้อมูล ให้สร้าง migration ไฟล์ใหม่เท่านั้น

### 6. รันระบบ

เปิด Terminal สองหน้าต่าง

หน้าต่างที่ 1 สำหรับ Laravel:

```bash
.\scripts\laravel.ps1 serve
```

หน้าต่างที่ 2 สำหรับ Vite:

```bash
npm run dev
```

จากนั้นเปิด [http://localhost:8000](http://localhost:8000)

หรือสามารถรันทั้งสองบริการพร้อมกันได้ด้วย:

```bash
tools\composer.bat run dev
```

## คำสั่งสำหรับ Production Build

```bash
npm run build
php artisan config:cache
php artisan route:cache
```

ควรตั้งค่า `APP_ENV=production`, `APP_DEBUG=false` และเปิด HTTPS ในไฟล์ `.env` ของ production

## โครงสร้างและฟังก์ชันที่มีในระยะแรก

- หน้าหลัก, สมัครสมาชิก, ค้นหาสมาชิก และ Member Dashboard
- การสมัครสมาชิกโดยไม่ใช้รหัสผ่าน
- การค้นหาด้วยเบอร์โทรศัพท์ พร้อมจำกัดความถี่ 10 ครั้ง/นาที
- Temporary Member Session อายุ 15 นาทีผ่าน HttpOnly Cookie
- หน้าจองคิวที่แสดงช่วงเวลา 10:00–19:00 และสถานะว่าง/ไม่ว่าง
- หน้า Coupon, Point History และหน้าจัดการสำหรับผู้ดูแล
- Design System ธีม Modern Luxury สีดำ ขาว และแดง พร้อม Dark Mode
- Schema สำหรับสมาชิก การจอง คะแนน คูปอง การแจ้งเตือน LINE และสิทธิ์พนักงาน

## ฟังก์ชันที่ต้องพัฒนาต่อ

- ตรวจสอบเวลาว่างและบันทึกการจองแบบป้องกันคิวซ้ำ
- สร้าง Booking No. และ QR Booking
- เชื่อมต่อ LINE Messaging API สำหรับแจ้งเตือนและ Reminder
- Check-in จากการ Scan QR และ Check-out
- ยืนยันใช้คูปองโดยพนักงาน
- คำนวณคะแนน: ยอดขายทุก 100 บาท ได้ 10 คะแนน
- ระบบ Admin Authentication และ Role/Permission แบบเต็มรูปแบบ
- REST API สำหรับการเชื่อมต่อภายนอก

## การตั้งค่า LINE Messaging API ในอนาคต

เมื่อต้องการเปิดใช้ LINE Messaging API ให้เพิ่มค่าต่อไปนี้ใน `.env`:

```env
LINE_CHANNEL_ACCESS_TOKEN=
LINE_OWNER_USER_ID=
```

ห้ามนำไฟล์ `.env` หรือ Access Token ขึ้น repository สาธารณะ

## ความปลอดภัย

ระบบวางโครงสร้างเพื่อรองรับ CSRF protection, validation, Eloquent/Query Builder สำหรับป้องกัน SQL Injection, Blade escaping สำหรับลดความเสี่ยง XSS, rate limit สำหรับการค้นหาเบอร์โทร และ activity log สำหรับการตรวจสอบการใช้งาน
