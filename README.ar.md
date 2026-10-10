 <p align="center">
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/actions/workflows/tests.yml"><img src="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/actions/workflows/tests.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/v/oktocode/sham-cash-sdk?label=packagist" alt="إصدار Packagist"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/php-v/oktocode/sham-cash-sdk?label=php" alt="إصدار PHP"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/dt/oktocode/sham-cash-sdk?label=downloads" alt="عدد التنزيلات"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/oktocode/sham-cash-sdk?label=license" alt="ترخيص MIT"></a>
</p>

<h1 align="center">ShamCash PHP SDK</h1>

<p align="center">
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/blob/master/README.md"><img src="https://img.shields.io/badge/lang-en-red.svg" alt="English"></a>
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/blob/master/README.ar.md"><img src="https://img.shields.io/badge/lang-ar-green.svg" alt="العربية"></a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><strong>Packagist</strong></a>
  &nbsp;·&nbsp;
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK"><strong>GitHub</strong></a>
  &nbsp;·&nbsp;
  <a href="https://ok2code.com"><strong>ok2code.com</strong></a>
</p>

<p align="center">
  مكتبة PHP للتكامل مع ShamCash، تتيح إنشاء فواتير الدفع واسترداد المبالغ وعرض المعاملات والتحقق من إشعارات الدفع الواردة (Webhooks).
</p>

<p align="center">
  <strong>إصدار المكتبة:</strong> 1.0.0 · <strong>إصدار ShamCash API:</strong> v1.0.0
</p>

## المتطلبات المسبقة

قبل البدء بالتكامل مع ShamCash، يجب تسجيل حساب تجاري موثّق، وإكمال إجراءات إنشاء الخدمة (Service Creation)، والحصول على بيانات اعتماد API اللازمة لربط تطبيقك بالخدمة.

للاطلاع على المعلومات المطلوبة وتعليمات التسجيل، راجع [دليل المتطلبات المسبقة وتسجيل الخدمة](prerequisite.ar.md).


## نبذة عن المكتبة (About)

تم تطوير هذه المكتبة بواسطة [OkToCode](https://ok2code.com).

تتولى المكتبة تشفير كل طلب قبل إرساله إلى ShamCash API، والتعامل مع نقاط الوصول الخاصة بإنشاء الفواتير واسترداد المبالغ وعرض المعاملات، بالإضافة إلى فك تشفير إشعارات Webhook التي ترسلها ShamCash إلى خادمك.

توفر المكتبة الوظائف التالية:

- **الفواتير واسترداد المبالغ (Bills and Refunds):** إنشاء فاتورة دفع واسترجاع بياناتها، وتنفيذ عملية استرداد المبلغ باستخدام مفتاح منع تكرار العملية (Idempotency Key) الذي تحدده.
- **المعاملات (Transactions):** استرجاع صفحة واحدة من المعاملات أو المرور على جميع الصفحات تلقائياً باستخدام `eachTransaction()`.
- **إشعارات Webhook:** تمرير نص طلب POST الأصلي إلى `parseWebhook()` للتحقق من الإشعار ومعالجة الحالات، مثل نجاح الدفع أو انتهاء صلاحية الفاتورة.
- **التعامل الآمن مع المبالغ:** تمثيل المبالغ المالية كسلاسل نصية عشرية (Decimal Strings) عند تمريرها إلى المكتبة وعند استلامها منها، لتجنب أخطاء الدقة الناتجة عن استخدام الأعداد العشرية العائمة (Floating-Point Numbers).

## التثبيت (Install)

ثبّت المكتبة باستخدام Composer:

```bash
composer require oktocode/sham-cash-sdk
```

الحزمة متاحة على [Packagist: oktocode/sham-cash-sdk](https://packagist.org/packages/oktocode/sham-cash-sdk).

مساحة الأسماء (Namespace) الخاصة بالمكتبة هي `OkToCode\ShamCash\`.

**المتطلبات:** PHP 8.2 أو إصدار أحدث.

## إعداد العميل (Configure the Client)

عند إنشاء الخدمة، ستحصل من ShamCash على `agentKey` ومفتاح سري مشفّر بصيغة Base64 بطول 32 بايت، بالإضافة إلى عنوان `baseUrl`. يجب الاحتفاظ بالمفتاح السري على الخادم وعدم كشفه للمستخدمين.

مثال على إنشاء عميل للاتصال بالخدمة:

```php
use OkToCode\ShamCash\Client;
use OkToCode\ShamCash\Enum\Currency;

$client = new Client(
    agentKey: getenv('SHAMCASH_AGENT_KEY'),
    secretKey: getenv('SHAMCASH_SECRET_KEY'),
    baseUrl: getenv('SHAMCASH_BASE_URL'),
    callbackUrl: 'https://agent.example/webhooks/shamcash',
    redirectUrl: 'https://agent.example/checkout/return',
);
```

أنشئ كائناً مستقلاً من `Client` لكل تاجر.

تستخدم المكتبة عميل Guzzle مُعدّاً مسبقاً، مع الإعدادات التالية:

- **مهلة الاتصال (Connect Timeout):** 5 ثوانٍ.
- **مهلة الطلب (Request Timeout):** 30 ثانية.
- **التحقق من TLS:** مفعّل للتحقق من أمان الاتصال.

إذا احتجت، أثناء الاختبارات أو عند دمج المكتبة مع نظام آخر، إلى استخدام آلية اتصال HTTP مختلفة، يمكنك تمرير عميل متوافق مع معيار PSR-18. تأكد من إبقاء التحقق من TLS مفعّلاً في ذلك العميل أيضاً.

## إنشاء فاتورة دفع (Create a Bill)

أنشئ فاتورة الدفع عندما يختار العميل ShamCash وسيلةً للدفع، وليس بمجرد حفظ سلة المشتريات لأول مرة.

تنتهي صلاحية فاتورة الدفع غير المدفوعة بعد 10 دقائق.

```php
$bill = $client->createBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    currency: Currency::Usd,
    note: 'Order 123',
);

header('Location: ' . $bill->paymentUrl);
```

وجّه العميل إلى الرابط الموجود في `paymentUrl` لفتح صفحة الدفع باستخدام متصفح النظام.

**مهم:** لا تفتح رابط الدفع داخل متصفح مضمّن (Embedded WebView)، لأن ذلك قد يمنع رابط الربط المباشر بتطبيق ShamCash (Deep Link) من العمل بشكل صحيح.

قيم العملات المدعومة في المثال:

- `Currency::Usd` تمثل العملة ذات المعرّف `1`.
- `Currency::Syp` تمثل العملة ذات المعرّف `2`.

مرّر المبلغ كسلسلة نصية عشرية، مثل `"10.50"`، بدلاً من تمريره كعدد عشري.

عند إعادة محاولة الدفع للطلب نفسه، استخدم قيمة جديدة وفريدة للمتغير `billNo`. على سبيل المثال، يمكنك استخدام `Order-123-1` للمحاولة الأولى و`Order-123-2` للمحاولة الثانية. يساعد ذلك على تجنّب الخطأ `bill number already exists` الناتج عن إعادة استخدام رقم فاتورة سبق إنشاؤه.

## عناوين Callback وRedirect

يُستخدم `callbackUrl` لتحديد العنوان الذي سترسل إليه ShamCash إشعارات Webhook المشفّرة باستخدام طلب POST. يمكنك ضبطه عند إنشاء العميل إذا كانت جميع الفواتير ستستخدم نقطة الوصول نفسها.

أما `redirectUrl` فهو العنوان الذي سيعود إليه متصفح المستخدم بعد إتمام عملية الدفع.

لا تضيف ShamCash قيمة `billNo` تلقائياً إلى عنوان إعادة التوجيه. لذلك، إذا كانت صفحة العودة تحتاج إلى معرفة الطلب المرتبط بالدفع، فعليك تضمين معرّف الطلب ضمن عنوان `redirectUrl` بنفسك.

كلا الخيارين اختياري عند إنشاء `Client`، كما أنهما اختياريان عند استدعاء `createBill()`، لكن ShamCash تشترط وجودهما في بيانات الطلب المشفّرة.

تعمل المكتبة وفق القواعد التالية لكل عنوان:

1. إذا مرّرت `callbackUrl` أو `redirectUrl` إلى `createBill()`، فستستخدم المكتبة القيمة التي مرّرتها لهذه الفاتورة.
2. إذا لم تمرّر أحد العنوانين، فستستخدم المكتبة القيمة المضبوطة مسبقاً عند إنشاء `Client`.
3. إذا بقي أي من العنوانين فارغاً بعد تطبيق هذه القواعد، فستطلق `createBill()` استثناء `InvalidArgumentException` ولن ترسل الطلب إلى ShamCash.

مثال:

```php
$client->createBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    currency: Currency::Usd,
);

$client->createBill(
    billNo: 'Order-456-1',
    amount: '20.00',
    currency: Currency::Usd,
    callbackUrl: 'https://agent.example/webhooks/shamcash/orders',
    redirectUrl: 'https://agent.example/orders/456/return',
);
```

في المثال الأول، ستستخدم الفاتورة عنوانَي `callbackUrl` و`redirectUrl` الافتراضيين المضبوطين عند إنشاء العميل.

أما في المثال الثاني، فستستخدم الفاتورة العنوانين المحددين في استدعاء `createBill()` فقط، دون تغيير القيم الافتراضية للعميل أو التأثير على الفواتير الأخرى.

إذا لم تمرّر أحد العنوانين إلى `createBill()`، فستحتفظ المكتبة باستخدام القيمة الافتراضية المقابلة له.

راجع المثال الكامل: [examples/create-bill.php](examples/create-bill.php).

## استرجاع بيانات فاتورة (Read a Bill)

يمكنك استرجاع بيانات فاتورة سبق إنشاؤها باستخدام `getBill()`:

```php
$bill = $client->getBill('Order-123-1');
```

استخدم هذه الدالة مرة واحدة فقط، بعد مرور 10 دقائق على الأقل من إنشاء الفاتورة، وفقط إذا لم يصلك إشعار Webhook الخاص بها.

**لا تستخدم `getBill()` للاستعلام المتكرر عن حالة الفاتورة (Polling).** اعتمد على إشعارات Webhook لمتابعة نتيجة الدفع، واستخدم الاستعلام عن الفاتورة كخيار احتياطي عند عدم وصول الإشعار.

راجع المثال: [examples/get-bill.php](examples/get-bill.php).

تكون قيمة `$bill->status` من النوع `BillStatus` إذا أعادت ShamCash معرّف حالة معروفاً للمكتبة. الحالات المدعومة هي:

- `pending`: الفاتورة بانتظار الدفع.
- `refund`: تم استرداد المبلغ.
- `expired`: انتهت صلاحية الفاتورة.
- `paid`: تم الدفع.
- `partly refunded`: تم استرداد جزء من المبلغ.

أما `$bill->raw`، فيحتوي على الكائن الذي أعادته ShamCash بعد فك ترميزه، بما في ذلك الحقول التي لم تتعامل معها المكتبة أو لم تربطها بخصائص محددة بعد.

## استرداد المبلغ (Refund)

عند طلب استرداد مبلغ، مرّر مفتاحاً لمنع تكرار العملية (Idempotency Key) يتراوح طوله بين 10 و100 محرف.

إذا انتهت مهلة الطلب دون الحصول على استجابة، فأعد المحاولة باستخدام **المفتاح نفسه**.

إذا كانت عملية الاسترداد قد نجحت سابقاً باستخدام هذا المفتاح، فستعيد ShamCash نتيجة عملية الاسترداد الأصلية بدلاً من تنفيذ عملية جديدة. أما إذا فشلت العملية، فسيبقى المفتاح متاحاً لإعادة المحاولة.

```php
$refund = $client->refundBill(
    billNo: $bill->billNo,
    amount: '10.50',
    idempotencyKey: $idempotencyKey,
    note: 'Customer return',
);
```

تتم عملية الاسترداد باستخدام عملة الفاتورة الأصلية، ولا يجوز أن يتجاوز مجموع مبالغ الاسترداد قيمة المبلغ الذي دفعه العميل فعلياً.

راجع المثال: [examples/refund-bill.php](examples/refund-bill.php).

## المعاملات (Transactions)

يمكنك استرجاع صفحة من المعاملات باستخدام `listTransactions()`، أو المرور على جميع الصفحات المتاحة تلقائياً باستخدام `eachTransaction()`.

```php
$page = $client->listTransactions(
    '2026-01-01',
    '2026-01-20',
    afterTranId: 0,
    limit: 1000
);

foreach ($client->eachTransaction('2026-01-01', '2026-01-20', limit: 1000) as $transaction) {
    // تكون قيمة $transaction->tranType هي payment أو refund
    // عندما يكون معرّف نوع المعاملة معروفاً للمكتبة.
}
```

تتعامل `eachTransaction()` مع ترقيم الصفحات تلقائياً، وفق الآلية التالية:

1. تتحقق من قيمة `hasMore` لمعرفة ما إذا كانت هناك صفحات إضافية.
2. تستخدم قيمة `lastReturnedTranId` من الصفحة السابقة وتمريرها في `afterTranId` عند طلب الصفحة التالية.
3. تتابع العملية حتى لا تعود هناك صفحات أخرى.

يجب أن تكون قيمة `limit` بين 10 و2500 معاملة، والقيمة الافتراضية هي `500`.

راجع المثال: [examples/list-transactions.php](examples/list-transactions.php).

## إشعارات Webhook

عند دفع الفاتورة أو انتهاء صلاحيتها، ترسل ShamCash طلب POST إلى العنوان المحدد في `callbackUrl`، ويحتوي جسم الطلب (Request Body) على بيانات مشفّرة بالشكل التالي:

```json
{ "encData": "..." }
```

مرّر جسم الطلب الأصلي مباشرةً إلى المكتبة لمعالجة الإشعار.

**مهم:** لا تقم بفك ترميز JSON ثم إعادة ترميزه قبل تمريره إلى المكتبة، لأن ذلك قد يغيّر قيمة `encData` ويؤدي إلى فشل فك التشفير.

مثال على معالجة إشعار Webhook:

```php
use OkToCode\ShamCash\Enum\BillStatus;
use OkToCode\ShamCash\Exception\CryptoException;

$rawBody = file_get_contents('php://input');

try {
    $event = $client->parseWebhook($rawBody);
} catch (CryptoException) {
    http_response_code(400);
    exit;
}

if ($event->status === BillStatus::Paid) {
    // $event->tranId هو معرّف عملية الدفع لدى ShamCash.
    // fulfillOrder($event->billNo, $event->tranId);
} elseif ($event->status === BillStatus::Expired) {
    // تكون قيمة $event->tranId هي null.
    // هذا يعني أن العميل لم يدفع خلال 10 دقائق.
    // releaseOrder($event->billNo);
}

http_response_code(200);
```

### معالجة الإشعارات بأمان

يجب تنفيذ التغيير المرتبط بالإشعار مرة واحدة فقط لكل زوج من القيمتين `billNo` و`status`.

تعيد ShamCash إرسال الإشعار إذا لم تحصل على استجابة HTTP 200 خلال 10 ثوانٍ. لذلك، قد يصل الإشعار نفسه أكثر من مرة، ويجب ألا يؤدي تكراره إلى تنفيذ الطلب أو تأكيد الدفع مرتين.

لضمان سلامة المعالجة:

- اجعل تحديث حالة الطلب أو تنفيذ إجراء الدفع قابلاً للتكرار بأمان (Idempotent).
- تحقّق من أن الفاتورة لم تُعالج مسبقاً بالحالة نفسها قبل تنفيذ أي إجراء.
- أعد استجابة HTTP 200 دون تأخير غير ضروري.
- نفّذ المهام البطيئة، مثل إرسال البريد الإلكتروني، بعد الاستجابة أو عبر نظام مهام خلفي (Background Queue).

تتحقق `parseWebhook()` أيضاً من صلاحية رمز الإشعار؛ إذ ترفض الرمز إذا انتهت صلاحيته وفق `exp`، أو إذا كانت قيمة `iat` تشير إلى وقت مستقبلي أبعد من المسموح به.

هامش اختلاف الساعة الافتراضي (Clock Skew) هو 30 ثانية.

راجع المثال الكامل لنقطة استقبال الإشعارات: [examples/webhook.php](examples/webhook.php).

## معالجة الأخطاء (Errors)

تختلف الاستثناءات التي تطلقها المكتبة حسب نوع الخطأ.

### 1. أخطاء الإدخال والإعدادات

تؤدي الأخطاء المحلية، مثل إدخال مبلغ غير صالح أو عدم تحديد عنوان URL مطلوب، إلى إطلاق `InvalidArgumentException` قبل إرسال أي طلب HTTP.

### 2. أخطاء ShamCash API

قد تعيد ShamCash أخطاء متعلقة بقواعد العمل (Business Errors) مع استجابة HTTP 200. في هذه الحالة، تطلق المكتبة الاستثناء `ApiException`.

يمكنك فحص الخصائص التالية لمعرفة تفاصيل الخطأ:

- `$exception->result`: نتيجة من النوع `ResultCode`.
- `$exception->resultCode`: رمز النتيجة الذي أعادته ShamCash.

### 3. أخطاء الاتصال والنقل

تطلق المكتبة `TransportException` عند حدوث مشكلات مثل:

- استجابات HTTP برموز `400` أو `404` أو `415` أو `500`.
- انتهاء مهلة الاتصال أو الطلب (Timeout).
- استلام JSON غير صالح.

أما إذا فشل فك تشفير الرمز أو التحقق منه، فتُطلق المكتبة `CryptoException`.

## معالجة إعادة محاولة إنشاء الفاتورة

قد تنجح ShamCash في إنشاء الفاتورة وتخزينها، لكن الاستجابة لا تصل إلى تطبيقك بسبب انقطاع الاتصال أو انتهاء المهلة.

إذا حاول تطبيقك إنشاء الفاتورة مرة أخرى باستخدام `billNo` نفسه، فقد تعيد ShamCash رمز النتيجة `1704`، الذي يشير إلى أن رقم الفاتورة موجود مسبقاً.

في هذه الحالة، لا تنشئ فاتورة جديدة برقم مختلف تلقائياً، لأن الفاتورة الأولى قد تكون موجودة بالفعل. بدلاً من ذلك، استرجع بيانات الفاتورة باستخدام `getBill()`.

```php
use OkToCode\ShamCash\Enum\ResultCode;
use OkToCode\ShamCash\Exception\ApiException;

try {
    $bill = $client->createBill(
        billNo: $billNo,
        amount: '10.50',
        currency: Currency::Usd
    );
} catch (ApiException $exception) {
    if ($exception->result !== ResultCode::BillNoAlreadyExists) {
        throw $exception;
    }

    $bill = $client->getBill($billNo);
}
```

يتعامل هذا المثال مع حالة وجود الفاتورة مسبقاً فقط، ويعيد إطلاق الاستثناء إذا كان الخطأ ناتجاً عن سبب آخر.

راجع المثال الكامل: [examples/retry-create-bill.php](examples/retry-create-bill.php).

**ملاحظات مهمة حول إعادة المحاولة:**

- لا تنفّذ المكتبة عمليات إعادة المحاولة تلقائياً.
- يمكن إعادة استدعاء `getBill()` و`listTransactions()` عند الحاجة.
- عند إعادة محاولة `refundBill()`، يجب استخدام مفتاح `idempotencyKey` نفسه لمنع تنفيذ عملية استرداد مكررة.

## التطوير (Development)

لتثبيت اعتماديات المشروع وتشغيل الاختبارات وفحوصات جودة الكود، نفّذ الأوامر التالية:

```bash
composer install
composer test
composer phpstan
composer cs
```

تُستخدم هذه الأوامر لتثبيت الحزم المطلوبة، وتشغيل الاختبارات، وفحص الأنواع الثابتة باستخدام PHPStan، والتحقق من توافق تنسيق الكود مع معايير المشروع.
