<div dir="rtl" align="center">

<img src="assets/img/logo.svg" alt="لوگوی مپین" width="230">

# مپین — پلتفرم تعاملی نقشه‌برداری و نظرسنجی اجتماعی

**مردم پاسخ می‌دهند. جهان پدیدار می‌شود.**

[![Live Demo](https://img.shields.io/badge/سایت-mpin.metora.ir-22d3ee?style=flat-square)](https://mpin.metora.ir/)
[![PHP](https://img.shields.io/badge/PHP-8%2B-777bb3?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-سازگار%20با%20MariaDB-4479a1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Leaflet](https://img.shields.io/badge/نقشه-Leaflet%20%2B%20OSM-199900?style=flat-square&logo=leaflet&logoColor=white)](https://leafletjs.com)
[![زبان](https://img.shields.io/badge/زبان-فارسی%20%2B%20English-ff6c38?style=flat-square)](#-زبان)
[![License](https://img.shields.io/badge/لایسنس-MIT-22d3ee?style=flat-square)](LICENSE)

**[English](README.md)** · **فارسی**

یک نظرسنجی بساز ← لینک را به اشتراک بگذار ← تماشا کن که پاسخ‌ها نقشه جهان را رنگ می‌زنند.

مپین سؤال‌های ساده را به تصویرسازی‌های جغرافیاییِ زنده تبدیل می‌کند — هر شرکت‌کننده می‌تواند یک پین تقریبی بگذارد و نتایج به‌مرور به خوشه، نقشه حرارتی و رتبه‌بندی کشورها تبدیل می‌شود.

**[دموی زنده ←](https://mpin.metora.ir/)** · **[گشت‌وگذار ←](https://mpin.metora.ir/explore)** · **[نقشه جهانی ←](https://mpin.metora.ir/map)**

</div>

---

## 🌍 امکانات

### 🗺️ نقشه تعاملی (قلب مپین)
- **خوشه‌بندی نشانگرها (Marker Clustering)** — هزاران پاسخ در خوشه‌های نرم و متحرک
- **نقشه حرارتی (Heatmap)** — با یک کلیک به نمای شدت
- **فیلتر بر اساس پاسخ‌ها** — مثلاً «فقط بازیکنان PC را نشان بده»
- **به‌روزرسانی زنده** — پاسخ‌های جدید بدون رفرش صفحه ظاهر می‌شوند
- **جستجوی مکان** — پرواز به هر شهر یا کشوری (Nominatim / OpenStreetMap)

### 📊 نظرسنجی‌ها
- انواع سؤال: **بله/خیر · تک‌انتخابی · چندانتخابی · متن کوتاه · عدد · امتیاز (۱-۵)**
- تا ۱۰ سؤال در هر نظرسنجی، با تنظیم اجباری/اختیاری برای هر سؤال
- عمومی (در گشت‌وگذار) یا خصوصی (فقط با لینک)
- زمان‌بندی شروع و پایان، مشارکت مهمان، پاسخ یک‌باره یا چندباره
- لینک اختصاصی (`/poll/cs2-players`)، کاور، ویرایش، بستن و حذف توسط سازنده

### 🔒 حریم خصوصی به‌صورت پیش‌فرض
| محافظت | توضیح |
|---|---|
| **گرید ~۵ کیلومتری** | مختصات قبل از ذخیره روی گرید snap می‌شوند — نقطه دقیق هرگز ذخیره نمی‌شود |
| **k-anonymity** | مناطق با کمتر از ۳ پاسخ به‌کل مخفی می‌مانند |
| **اختیاری بودن** | گذاشتن پین همیشه انتخاب کاربر است؛ GPS فقط با اجازه صریح مرورگر |
| **بدون IP خام** | فقط hash یک‌طرفه و salted از IP و fingerprint درشت مرورگر |
| **حذف داده** | کاربر **و مهمان** می‌توانند همه پاسخ‌هایشان را از صفحه «داده‌های من» حذف کنند |

### 📈 تحلیل‌ها
رشد مشارکت، نمودار هر سؤال، هیستوگرام اعداد، توزیع امتیاز، کشورها و شهرهای برتر، خروجی CSV/JSON

### 🛡️ مدیریت
پنل ادمین کامل: کاربران، نظرسنجی‌ها (ویژه/تأیید/بستن/حذف)، دسته‌بندی‌ها، صف گزارش‌ها، **گزارش رخدادهای مدیریتی (Audit Log)** و تنظیمات سایت

## 🌐 زبان

| زبان | جهت | تاریخ | اعداد |
|---|---|---|---|
| English | LTR | Gregorian | 123 |
| فارسی | RTL | شمسی (جلالی) | ۱۲۳ |

انتخاب زبان از بالای صفحه — با فونت وزیرمتن و چیدمان کاملاً راست‌به‌چپ.

## 🧱 تکنولوژی

PHP 8 خالص + MySQL/MariaDB (PDO) + Leaflet + Chart.js + Vanilla JS — بدون build، بدون Node.js، بدون WebSocket؛ اجرا روی هر هاست اشتراکی cPanel.

**امنیت:** CSRF روی همه POST ها · Prepared Statements · Rate Limiting چندلایه (اکانت + کوکی مهمان + hash IP + hash fingerprint مرورگر) · Honeypot · سشن هاردند · Re-encode آپلودها

## 🚀 نصب روی cPanel

1. فایل‌های ریپو را در document root دامنه کپی کنید (File Manager → Upload → Extract)
2. از cPanel یک دیتابیس MySQL و یوزر بسازید و دسترسی کامل بدهید
3. آدرس `your-domain.com/install/` را باز کنید، اطلاعات دیتابیس و ادمین را وارد کنید و **Install** بزنید
4. پوشه `install/` را حذف کنید — تمام! 🎉

## 👤 سازنده

**امیر — [@Daronely](https://github.com/Daronely)**

[![Telegram](https://img.shields.io/badge/تلگرام-@daronelydev-229ED9?style=for-the-badge&logo=telegram&logoColor=white)](https://t.me/daronelydev)
[![GitHub](https://img.shields.io/badge/GitHub-Daronely-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/Daronely)
[![تماس](https://img.shields.io/badge/تماس-%2B98%20999%20996%200295-25D366?style=for-the-badge&logo=whatsapp&logoColor=white)](tel:+98999960295)
[![وب‌سایت](https://img.shields.io/badge/وب‌سایت-mpin.metora.ir-22d3ee?style=for-the-badge&logo=googlechrome&logoColor=white)](https://mpin.metora.ir/)

---

<div dir="rtl" align="center">

**مپین — مردم پاسخ می‌دهند. جهان پدیدار می‌شود.** 🌍📍

ایده را دوست داشتی؟ ⭐ بزن!

</div>
