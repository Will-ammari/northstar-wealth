# ابدأ من هون — تسليم Talent Syria

**المشروع النهائي المقترح:** Laravel 12 + MySQL 8.4 + Next.js/TypeScript + OpenAI Responses API function calling بدون LangChain.

## شو الملف اللي لازم ترسله؟

هذا المستودع بعد ما **تختبره فعلياً على جهازك**، إضافة إلى فيديو تسجيل شاشة **بصوتك 10–15 دقيقة**. أرسل رابط GitHub ورابط الفيديو بالرد على نفس رسالة الشركة **قبل نهاية يوم الأحد 25-10-2026 بتوقيت دمشق**. لو تعذّر تنفيذ كل الكود، ملف `docs/PLAN.md` هو الحد الأدنى المقبول من ناحية التنفيذ، لكن الفيديو مطلوب.

## الملفات المهمة داخل المشروع

- `README.md` — التشغيل والأوامر ودخول المستخدمين التجريبيين.
- `docs/PLAN.md` — الخطة الهندسية التي تحتاجها الشركة.
- `docs/SUMMARY_AR.md` — التقرير الهندسي المفصل **بالعربية**: شو عملنا وليش.
- `docs/ARCHITECTURE.md` و`docs/DATA_MODEL.md` — المعمارية وقاعدة البيانات والقيود والفهارس.
- `docs/SECURITY.md` — الصلاحيات والـAI والأمان.
- `docs/INTERVIEW_QA.md` — 22 سؤال مقابلة مع أجوبة هندسية.
- `docs/VIDEO_SCRIPT.md` — سيناريو فيديو بالإنكليزي مع أوقات ولقطات تشرحها.
- `docs/VERIFICATION.md` — الفحوصات الفعلية والقيود المتبقية.
- `docs/AI_USAGE.md` — كيف توضح استخدام ChatGPT بصورة صريحة ومهنية.

## أول أربع خطوات على جهازك

1. `docker compose up -d mysql` لتشغيل MySQL.
2. داخل `backend` انسخ `.env.example` إلى `.env`، ثبّت Composer وشغّل migrate وseed وartisan serve حسب README.
3. داخل `frontend` انسخ `.env.example` إلى `.env.local`، اضبط BFF_SESSION_SECRET وشغّل npm install ثم npm run dev.
4. **أنشئ قاعدة `portfolio_test` منفصلة** قبل `php artisan test`. مثلاً ادخل MySQL محلياً بصلاحية root ونفذ:

```sql
CREATE DATABASE IF NOT EXISTS portfolio_test;
GRANT ALL PRIVILEGES ON portfolio_test.* TO 'portfolio'@'%';
```

بعدها شغّل اختبارات Laravel والبناء والـTypecheck والـLint حسب README. إذا ظهر أي Error لازم يتصلح قبل ادعاء نجاح المشروع. بيانات `owner@example.test` و`viewer@example.test` و`outsider@example.test` تجريبية فقط.

## شو تتحقق منه بالفيديو؟

- Admin/Owner يضيف أصل أو يسجل معاملة، وتظهر البيانات الجديدة.
- Viewer ما بيقدر يكتب، وحتى Direct API ترجع 403.
- Oversell ما بيسمح بقيمة سالبة؛ تكرار UUID ما يكتب معاملتين.
- AI Assistant (إذا عندك OpenAI API Key) يجيب معلومات من نفس الـLaravel API المحمي مع تدفق نص؛ ما بيقدر ينفّذ كتابة.
- اشرح قرار MySQL بدل PostgreSQL، وRaw Function Calling بدل LangChain، وقائمة ما تم تأجيله.

## مهم جداً

النسخة تم توليدها بمساعدة AI. نجاح الـPHP/TypeScript syntax checks **لا يثبت** تشغيل Laravel، نجاح migrations، TypeScript build أو LLM integration. بعض خطوات التحقق لازم تعملها أنت لأن بيئة الإنشاء ما فيها Composer/MySQL/npm Registry. لا ترفع مستودعاً وتقول إنه جاهز للإنتاج أو أن كل الاختبارات خضراء قبل حصولك على دليل حقيقي. تفاصيل هذا القيد موثّقة بملف `docs/VERIFICATION.md`.
