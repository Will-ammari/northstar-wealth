# التقرير الهندسي العربي — Northstar Wealth

**المرشح:** William Ammari  
**المهمة:** Talent Syria — Wealth Portfolio Tracker  
**التصميم النهائي:** Laravel 12 + MySQL 8.4 + Next.js 16 / TypeScript + OpenAI Responses API Function Calling **بدون LangChain**.  
**الحالة الفعلية (24 أكتوبر 2026):** تم تشغيل Laravel والواجهة محلياً على Windows/XAMPP مع MariaDB 10.4.32. نجح 16 اختبار Laravel (34 assertion)، ونجحت أوامر `npm run lint` و`npm run typecheck` و`npm run build`، بالإضافة لفحوصات يدوية على الدخول والصلاحيات والمعاملات. لم يتم اختبار OpenAI API المباشر بسبب عدم توفر API Key، ولم يتم التحقق من GitHub Actions أو توافق MySQL 8.4 بشكل مستقل. هذا Prototype وليس نظاماً إنتاجياً.

## 1. لماذا هذا الاختيار يناسب خبراتك؟

وفق CV وتقريرك الهندسي السابق، أقوى خبراتك موثقة بـLaravel والـDomain Modeling والـTransaction Boundaries وRow Locking وREST API وAuthentication/Authorization. أما Next.js وTypeScript وLangChain فقلت إنك لا تملك خبرة عملية بها. ركّزنا على جعل Laravel صاحب منطق الأعمال، مع واجهة Next.js صغيرة ومفهومة لإثبات قدرتك على العمل ضمن متطلبات الوظيفة. اخترنا MySQL لأنك تعرفه وهو يلبّي نطاق المشروع؛ لم تكن هناك حاجة هندسية ملحّة لفرض PostgreSQL جديد. استبعدنا LangChain لأن الوكيل يضم أداتين فقط ويمكن تنفيذهما عبر Function Calling مباشرة؛ الاختبار يسمح بـRaw LLM API، لكن الوظيفة تطلب LangChain ولذلك نبقى صريحين أن هذا المشروع لا يثبت خبرة فيه.

**ملاحظة:** الشركة لا تضمن التوظيف لمن يستخدم تقنيات معيّنة، وهي صرّحت بأن جودة القرارات ونطاق التنفيذ والفهم أهم من كمية الكود. لا تدّعِ خبرة ثلاث سنوات بالتقنيات الجديدة ولا أن كل الفحوصات نجحت قبل تشغيلها.

## 2. ماذا نُفّذ في المصدر؟

### A. Laravel Backend (مالك الدومين)

- مسار `backend/app/Http/Controllers/Api/`: Controller لكل عملية، يستقبل الطلب ويفحص Policy ويستدعي Action/Query، بدون حشر business rules في Controller.
- مسار `backend/app/Http/Requests/`: Form Requests تتحقق من إدخالات العميل: نوع الأصل، دقة الكسور، العدد والسعر، UUID فريد من ناحية الشكل. الـValidation ليست بديلاً عن الصلاحيات.
- مسار `backend/app/Policies/PortfolioPolicy.php`: `view` لأي عضو، و`mutate` للـowner/editor فقط. كل API يمر بهذا التحقق، والوكيل يستخدم نفس الـAPI المحمي.
- `backend/app/Actions/CreateAsset.php`: يفتح أصلاً ويكتب سجل تقييم ومعاملة افتتاحية ضمن Transaction واحدة (نجاح أو Rollback كامل).
- `backend/app/Actions/RecordTransaction.php`: يحمي سجل الأصل بـ`lockForUpdate()`، ويزيد/ينقص الكمية بعمليات `DECIMAL` ضمن MySQL، ويمنع oversell، ويسجل حركة دائمة. مفتاح Idempotency فريد لكل محفظة؛ تكرار نفس الطلب يرجع نفس الحركة وتغيير التفاصيل يرفض.
- `backend/app/Actions/RecordValuation.php`: يحفظ تقييم جديد بالتاريخ ويُحدّث السعر الحالي؛ لا يغيّر سعر تنفيذ عمليات شراء/بيع قديمة.
- `backend/app/Queries/PortfolioDashboard.php`: مجموع قيم الأصول وتوزيعها يحسب في SQL؛ قائمة الأصول محدودة بـ100 والحركات بـ10 لتقييد حجم الاستجابة.

### B. قاعدة البيانات MySQL 8.4

الجداول الرئيسية: `users`، `portfolios`، `portfolio_memberships`، `assets`، `portfolio_transactions`، `asset_valuations` بالإضافة لجداول المصادقة. استخدمنا `DECIMAL(24,8)` للحسابات الرقمية الدقيقة وComposite FK حتى لا يمكن إنشاء حركة بمحفظة مختلفة عن محفظة أصلها. هناك UNIQUE للعضوية ولـIdempotency، وCHECK لمنع كميات وأسعار سالبة والأدوار غير المعروفة. cash بالدولار فقط وسعر وحدته 1. جميع هذه الضمانات تحتاج تجربة Migration واختبارات فعلية على MySQL 8.4 قبل الجزم أنها تعمل في البيئة المستهدفة.

**معنى الكفاءة:** الصفوف المفهرسة والاستعلامات المحددة وLock على سجل الأصل تساعد، لكنها ليست إثباتاً للأداء تحت حمل مرتفع. المشروع ليس benchmark ولا نظام تداول أو محاسبة مالية مكتملة. لازالت هناك حاجة Pagination وLoad Testing وMonitoring قبل الادعاء بالقدرة على ملايين العمليات.

### C. Next.js + TypeScript

واجهة Responsive فيها Login وDashboard مع قيمة المحفظة، توزيع الأصول، Holdings، سجل نشاط، نماذج إضافة وتسجيل وتقييم، ورسائل Loading/Error والـAssistant. تواصل المتصفح حصراً مع Next.js BFF؛ BFF يحتفظ بتوكن Laravel داخل Cookie مشفّرة HttpOnly ويضيفه لطلبات Laravel من السيرفر. الـFrontend تُظهر الأزرار بحسب Role لتحسين UX لكن قرار السماح النهائي حصراً عند Laravel.

ليش TypeScript؟ لتوصيف شكل الـResponse، والـProps، والـStates، وتقليل أخطاء الربط ضمن الكود. لا تدّعِ End-to-End Type Safety تلقائي: عقد OpenAPI وتوليد Types والتحقق المتبادل بين الطبقتين خطوة موثقة للمستقبل.

### D. AI Assistant — Raw Function Calling

المستخدم بيسأل سؤالاً؛ السيرفر يفحص الجلسة و`Origin` والمدخل، ثم يسأل Laravel إذا للمستخدم صلاحية قراءة المحفظة، مع حد طلبات. بعدها يطلب من OpenAI Responses API اختيار الأدوات. الأدوات المتاحة اثنتان فقط: `get_portfolio_summary` و`list_holdings`. لا تحتوي الأدوات على userId أو portfolioId كمعلمات للنموذج؛ `portfolioId` مأخوذ من route موثّق، وكل أداة تعيد استدعاء Laravel API المحمي. بعدها ينفذ النموذج صياغة رد نصي ويتم بثه على شكل SSE إلى المتصفح.

**السبب الأمني:** حتى لو قال المستخدم 'تجاهل التعليمات واشتري سهم' لا توجد أداة شراء ضمن الواجهة للوكيل. النموذج ليس جهة صلاحيات، وPrompt وحده لا يحمي النظام. وجود `OPENAI_API_KEY` ضروري؛ من دونه يرجع الخطأ 503. التكامل لم يُختبر مع المفتاح الحقيقي هنا، فلا تعرض مثالاً مولّداً على أنه نتيجة فعلية.

### E. CI + اختبارات وتوثيق

أُجريت اختبارات Laravel محلياً على MariaDB 10.4.32 بتاريخ 24-10-2026، وكانت النتيجة 16 اختباراً ناجحاً مع 34 assertion. ونجحت فحوصات Frontend الثلاثة (`lint` و`typecheck` و`build`)، وتجارب المتصفح وAPI الموضحة في `VERIFICATION.md`. ملف GitHub Actions موجود لكن لا توجد نتيجة تشغيل CI مؤكدة، كما أن اتصال OpenAI المباشر والـStreaming لم يُختبرا.

## 3. كيف ترتبط المعمارية بالهدف؟

المستخدم يرسل طلباً إلى Next.js. Next.js يتحقق من الجلسة (ويفحص Origin بطلبات POST) ثم يمرر Bearer Token إلى Laravel. Laravel يحدد المستخدم ويطبق PortfolioPolicy، بعده Action أو Query، ثم MySQL يتحقق من FK/UNIQUE/CHECK ويتعامل مع SQL transaction. إذا فشلت أي خطوة حرجة ضمن Transaction، تُلغى العملية بالكامل. هذا التسلسل يقصّد أن لا تكون صلاحيات React ولا نص الـLLM هي الأساس الأمني.

## 4. لماذا فصلنا Controller وAction وQuery؟

`Controller`: HTTP adapter، لا يعرف تفاصيل إجراء الخصم. `FormRequest`: حدود بيانات HTTP. `Policy`: هل يُسمح للمستخدم؟ `Action`: كيف تنفّذ عملية متسقة بحدود Transaction؟ `Query`: كيف نستخرج البيانات المقروءة؟ هذا أبسط من بناء Clean Architecture متعددة طبقات وهمية، وبنفس الوقت قابل لاختبار كل مسؤولية بوضوح. مناسب لخبرتك السابقة بالـActions/Queries.

## 5. قرارات مهم تفهمها وتحفظ منطقها

- `DECIMAL(24,8)`: الأرقام المالية الحساسة تُخزّن وتحسب في SQL؛ لا نعتمد على Float بالـJS. قيمة العرض قد تتعرض لتقريب بصري فقط.
- `lockForUpdate()`: يمنع طلبين من تعديل كمية نفس الأصل بالتوازي دون تسلسل؛ لا يعني منع كل نوع من السباقات في الكون.
- Conditional sell: `quantity >= requested` شرط بعملية UPDATE نفسها، وليس فحصاً سابقاً قابل للسباق.
- Idempotency: مفتاح UUID مرفق مع العملية، UNIQUE في MySQL، ومقارنة تفاصيل الطلب عند الإعادة. حتى إن حصل تضارب بين أصلين، يُلغى الإدخال الخاسر وتُفحص الحركة الناجحة بعد Rollback.
- Portfolio Membership: المستخدم قد يكون Viewer في محفظة وEditor في أخرى؛ الـPolicy تفحص العضوية الخاصة بالمحفظة الحالية.
- Valuation history ≠ execution price: سعر التنفيذ حقيقة تاريخية، والتقييم الحالي Snapshot مستقل.
- MySQL بدلاً من PostgreSQL: يدعم الاحتياجات الحالية وخبرتك فيه أعلى؛ ليس معنى ذلك أنه أسرع دائماً.
- Raw LLM API بدلاً من LangChain: يقدّم Tool Loop واضحاً بأداتين بدون Framework Bloat؛ إذا توسع العمل لاحقاً يمكن تقييم إطار orchestration.
- BFF: يمنع كشف الـBearer Token لجافاسكربت المتصفح، لكن يضيف تكلفة تشغيل Tier منفصل.
- Deferred jobs/realtime: عدم إضافتها قرار Scope Control واعٍ، لا نقص معرفة يجب إخفاؤه.

## 6. نقاط الضعف والمخاطر المتبقية

هذه نسخة أولية تم تشغيلها واختبار مساراتها الأساسية محلياً، لكن لم يتم اختبار اتصال OpenAI الحقيقي، أو التزامن باستخدام اتصالين منفصلين لقاعدة البيانات، أو النشر الإنتاجي، أو الضغط والأداء. كما لم يتم التحقق من Docker Compose وGitHub Actions وتشغيل MySQL 8.4 مباشرة. لذلك لا نصفها بأنها Production-ready، رغم نجاح البناء والفحوصات المحلية الموثّقة.

## 7. المطلوب منك قبل إرسال الإيميل

1. افتح `README.md` ونفّذ تثبيت وتشغيل MySQL وLaravel وNext.js.
2. اكتب ملف `.env` حقيقي لكل جزء بدون رفع أسرار، وشغّل `php artisan migrate` و`php artisan db:seed`.
3. شغّل PHPUnit وTypecheck وLint وBuild، وصحح أي إخفاقات. **لا ترسل من دون معرفة حالتها الحقيقية.**
4. ادخل owner وviewer وoutsider؛ جرّب فشل صلاحيات الـViewer والـOutsider، وفشل oversell، وإعادة نفس UUID مرتين.
5. لتفعيل الـAssistant استخدم API key تجريبي ببيانات وهمية، وتحقق أنه يسرد بيانات من Laravel وأن Streaming حقيقي.
6. حدّث `VERIFICATION.md` بالنتائج ولقطة/أدلة التنفيذ؛ ثم ارفع GitHub Repo يمكن للشركة قراءته.
7. سجّل فيديو بصوتك 10–15 دقيقة يشرح ما اختبرته فعلياً، ثم أرسل رابط GitHub ورابط الفيديو بالرد على بريد Talent Syria قبل موعد دمشق.

## 8. هل المشروع يعطي أفضلية بالتوظيف؟

ممكن يبرز قصتك الهندسية: مهندس Laravel يفهم المعاملات والصلاحيات ويستخدم AI ليبني واجهة Next.js ووكيل أدوات صغير. لكن لا يوجد ضمان قبول. أفضل إشارة تقدّمها هي **قرار هندسي مبرر + اختبار يثبت سلوكاً حقيقياً + شرح صادق للحدود**، وليس استخدام أسماء Frameworks كثيرة.

للمرجع التقني راجع `PLAN.md` و`DATA_MODEL.md` و`ARCHITECTURE.md` و`SECURITY.md` وروابط التوثيق الرسمي فيها. لتوقعات المقابلة راجع `INTERVIEW_QA.md`، وللفيديو `VIDEO_SCRIPT.md`.
