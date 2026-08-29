````markdown
# Changelog

تمام تغییرات قابل توجه در این پروژه در این فایل ثبت می‌شود.

## [Fork - behf/VPNMarket] - 2026-08-29

### 🔐 امنیت و احراز هویت
- **Bearer Token Authentication برای Sanaei**: جایگزینی احراز هویت cookie-محور با Bearer Token ایمن‌تر و قابل‌اعتماد‌تر
  - حذف نیاز به CSRF token
  - احراز هویت بدون‌نشست (Stateless)
  - بیشتر قابل‌اعتماد و ایمن برای Sanaei/X-UI

### ✨ تغییرات کد
- **Modules/Reseller/Services/Vpn/SanaeiService.php**
  - حذف `CookieJar` و احراز هویت cookie-محور
  - حذف `login()` method (دیگر نیازی نیست)
  - بروزرسانی `getClient()` برای استفاده از `Http::withToken()` 
  - اضافه شدن `api_token` validation در تمام methods

- **Modules/Reseller/Models/VpnServer.php**
  - اضافه شدن `api_token` به array `$fillable`
  - حذف `username` و `password` از `$fillable`
  - اضافه شدن `$hidden = ['api_token']` برای امنیت

- **Modules/Reseller/Filament/Resources/VpnServerResource.php**
  - حذف فیلدهای `username` و `password` از فرم
  - اضافه شدن فیلد `api_token` با password input و reveal option
  - بروزرسانی متن راهنما برای راهنمایی کاربران

### 📚 مستندات
- بروزرسانی `README.md` برای ارجاع به fork
- بروزرسانی `install.sh` برای دانلود از fork
- بروزرسانی `update.sh` برای ارجاع صحیح به مخزن
- اضافه شدن نکات مهم درباره Bearer Token

### 🎯 نحوه استفاده برای Sanaei

1. به پنل Sanaei بروید: **Settings → Security → API Token**
2. توکن API را کپی کنید
3. هنگام افزودن سرور در VPNMarket:
   - فیلد **"توکن API (Bearer Token)"** را پر کنید
   - دیگر نیازی به نام کاربری و رمز عبور نیست
4. تمام! ✅

---

## [Original] - Previous Versions

برای مشاهده تغییرات نسخه اصلی، به [arvinvahed/VPNMarket](https://github.com/arvinvahed/VPNMarket) مراجعه کنید.

---

## نحوه مشارکت

اگر باگی پیدا کردید یا پیشنهادی دارید:

1. [Issues](https://github.com/behf/VPNMarket/issues) را بررسی کنید
2. یک Issue جدید ایجاد کنید یا موجود را نظارت کنید
3. PR را submit کنید با تغییرات خود

---

**آخرین آپدیت:** 2026-08-29
**مخزن:** https://github.com/behf/VPNMarket
````
