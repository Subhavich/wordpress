# WordPress (Docker Compose)

ติดตั้ง WordPress + MariaDB + phpMyAdmin ผ่าน Docker Compose พร้อมติดตั้ง WordPress และสร้าง admin user ให้อัตโนมัติ

## Quick start / เริ่มเร็ว

เปิด Docker Desktop แล้วรันคำสั่งเดียว (Windows: `.\setup.ps1`, macOS/Linux/Git Bash: `./setup.sh`) จะติดตั้ง WordPress, Elementor (ฟรี),
ปลั๊กอินเสริม, ข้อมูลตัวอย่างบล็อก และเลย์เอาต์หน้าแรกให้ครบ — Open Docker Desktop and run one command
(Windows: `.\setup.ps1`, macOS/Linux/Git Bash: `./setup.sh`): it installs WordPress, Elementor (free), the add-on plugins, mock blog data and
the home-page layout. Details, what was tested, and how to move the design to another WordPress: **[INSTALL-GUIDE.md](INSTALL-GUIDE.md)**.

## Services

| Service      | Container    | Image              | Port (default)         |
|--------------|--------------|---------------------|-------------------------|
| `db`         | `wp_db`      | `mariadb:11`        | internal only (3306)    |
| `wordpress`  | `wp_app`     | `wordpress:latest`  | `8080` → 80             |
| `wpcli`      | `wp_cli`     | `wordpress:cli`     | - (รันครั้งเดียวแล้วจบ) |
| `phpmyadmin` | `wp_pma`     | `phpmyadmin:latest` | `8081` → 80             |

## เริ่มใช้งาน

1. คัดลอก [.env.example](.env.example) เป็น `.env` แล้วแก้ไขค่าให้เป็นของตัวเอง (ดูรายละเอียดด้านล่าง)
   ```sh
   cp .env.example .env
   ```
2. รัน:
   ```sh
   docker compose up -d
   ```
3. รอสักครู่ให้ `wp_cli` ติดตั้ง WordPress ให้เสร็จ (เช็คได้ด้วย `docker compose logs wpcli`)
4. เปิดใช้งาน:
   - เว็บไซต์: http://localhost:8080
   - หน้า Admin: http://localhost:8080/wp-login.php
   - phpMyAdmin: http://localhost:8081

## ตัวแปรใน `.env`

| ตัวแปร               | ความหมาย                                   |
|----------------------|---------------------------------------------|
| `DB_NAME`            | ชื่อฐานข้อมูล WordPress                      |
| `DB_USER`            | user ของฐานข้อมูล                           |
| `DB_PASSWORD`        | รหัสผ่าน user ฐานข้อมูล                      |
| `DB_ROOT_PASSWORD`   | รหัสผ่าน root ของ MariaDB                    |
| `WP_PORT`            | พอร์ตที่เปิดเว็บไซต์ (ค่าเริ่มต้น 8080)        |
| `PMA_PORT`           | พอร์ตที่เปิด phpMyAdmin (ค่าเริ่มต้น 8081)     |
| `WP_URL`             | URL ของไซต์ ใช้ตอนติดตั้งอัตโนมัติ             |
| `WP_TITLE`           | ชื่อไซต์                                     |
| `WP_ADMIN_USER`      | ชื่อผู้ใช้ admin ที่จะสร้างให้อัตโนมัติ         |
| `WP_ADMIN_PASSWORD`  | รหัสผ่าน admin                               |
| `WP_ADMIN_EMAIL`     | อีเมล admin                                  |

⚠️ **ต้องเปลี่ยนค่า password ทุกตัวจาก placeholder ก่อนใช้งานจริง** ไฟล์นี้ชื่อ `.env` (มีจุดนำหน้า) ห้ามเปลี่ยนชื่อ เพราะ Docker Compose จะโหลดตัวแปรจากไฟล์นี้เท่านั้น

## การติดตั้งอัตโนมัติทำงานอย่างไร

Service `wpcli` ใช้ image `wordpress:cli` รันสคริปต์ [wp-install.sh](wp-install.sh) ซึ่งจะ:
1. รอให้ไฟล์หลักของ WordPress (`wp-load.php`) พร้อมใน volume ที่แชร์กับ `wordpress`
2. เช็คว่าติดตั้งไปแล้วหรือยัง (`wp core is-installed`) — ถ้าติดตั้งแล้วจะข้าม
3. ถ้ายังไม่ติดตั้ง จะรัน `wp core install` โดยใช้ค่า admin จาก `.env`

Container นี้รันครั้งเดียวแล้ว exit (ไม่ restart) จึงเป็นปกติที่จะเห็นสถานะ `Exited (0)` หลังติดตั้งเสร็จ

## คำสั่งที่ใช้บ่อย

```sh
# ดู log การติดตั้ง
docker compose logs wpcli

# ดูสถานะ container ทั้งหมด (รวม exited)
docker compose ps -a

# ปิด stack (เก็บข้อมูลไว้ใน volume)
docker compose down

# ปิด stack และล้างข้อมูลทั้งหมด (ฐานข้อมูล + ไฟล์ WordPress)
docker compose down -v

# รัน wp-cli คำสั่งอื่น ๆ เพิ่มเติม เช่น ติดตั้ง plugin
docker compose run --rm wpcli wp plugin install <plugin-slug> --activate --allow-root
```

## ไฟล์อื่น ๆ

- [docker-compose.yml](docker-compose.yml) — นิยาม service ทั้งหมด
- [uploads.ini](uploads.ini) — ปรับ php.ini สำหรับ upload (ขนาดไฟล์, memory limit, ฯลฯ) mount เข้าไปใน container `wordpress`
- [wp-install.sh](wp-install.sh) — สคริปต์ติดตั้ง WordPress อัตโนมัติที่ใช้โดย service `wpcli`
- [.env.example](.env.example) — ต้นแบบตัวแปรสภาพแวดล้อม คัดลอกเป็น `.env` ก่อนใช้งาน (`.env` จริงไม่ถูก commit เข้า git เพราะมีรหัสผ่าน)
