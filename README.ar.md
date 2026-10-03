<p align="center">
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/actions/workflows/tests.yml"><img src="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/actions/workflows/tests.yml/badge.svg" alt="CI"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/v/oktocode/sham-cash-sdk?label=packagist" alt="Packagist version"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/php-v/oktocode/sham-cash-sdk?label=php" alt="PHP version"></a>
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><img src="https://img.shields.io/packagist/dt/oktocode/sham-cash-sdk?label=downloads" alt="Packagist downloads"></a>
  <a href="LICENSE"><img src="https://img.shields.io/packagist/l/oktocode/sham-cash-sdk?label=license" alt="MIT license"></a>
</p>

<h1 align="center">ShamCash PHP SDK</h1>

<p align="center">
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/blob/master/README.md"><img src="https://img.shields.io/badge/lang-en-red.svg" alt="en"></a>
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK/blob/master/README.ar.md"><img src="https://img.shields.io/badge/lang-ar-green.svg" alt="ar"></a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/oktocode/sham-cash-sdk"><strong>Packagist</strong></a>
  &nbsp;&middot;&nbsp;
  <a href="https://github.com/OkToCode-L-L-C/Sham-Cash-PHP-SDK"><strong>GitHub</strong></a>
  &nbsp;&middot;&nbsp;
  <a href="https://ok2code.com"><strong>ok2code.com</strong></a>
</p>

<div dir="rtl">

<p align="center">
  أنشئ فواتير ShamCash، واسترد المدفوعات، واعرض الحركات، وتحقق من إشعارات الويب من PHP.
</p>

## حول

نفّذته [OkToCode](https://ok2code.com).

يشفر SDK كل طلب، ويستدعي نقاط الفواتير والاسترداد والحركات، ويفك تشفير إشعار الويب الذي يرسله ShamCash إلى خادمك.

- **الفواتير والاسترداد.** أنشئ فاتورة، ثم اقرأها، واسترد قيمتها بمفتاح منع تكرار تختاره أنت.
- **الحركات.** اقرأ صفحة واحدة، أو تنقّل بين كل الصفحات عبر `eachTransaction()`.
- **إشعارات الويب.** مرّر متن طلب POST كما وصل إلى `parseWebhook()` ثم فرّع المعالجة بين حالة الدفع وانتهاء الصلاحية.
- **مبالغ دقيقة.** يبقى المبلغ سلسلة عشرية عند الإرسال وعند الاستقبال.

## التثبيت

</div>

```bash
composer require oktocode/sham-cash-sdk
```

<div dir="rtl">

الحزمة: [oktocode/sham-cash-sdk](https://packagist.org/packages/oktocode/sham-cash-sdk). فضاء الأسماء هو `OkToCode\ShamCash`. يتطلب PHP 8.2 أو أحدث.

## إعداد العميل

يمنحك ShamCash مفتاح الوكيل، وسرًا بطول 32 بايتًا مشفرًا بصيغة Base64، ورابط الأساس عند إنشاء الخدمة. أبقِ السر على الخادم.

</div>

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

<div dir="rtl">

أنشئ عميلًا واحدًا لكل تاجر. يبني SDK عميل Guzzle بمهلة اتصال قدرها 5 ثوانٍ ومهلة طلب قدرها 30 ثانية، ويتحقق من TLS. مرّر عميل PSR-18 الخاص بك عندما يحتاج اختبار أو جسر لاحق إلى مكدس HTTP مختلف، وأبقِ التحقق من TLS مفعّلًا على ذلك العميل.

## إنشاء فاتورة

أنشئ الفاتورة عندما يختار الزبون ShamCash، لا عند حفظ السلة أول مرة. تنتهي صلاحية الفاتورة غير المدفوعة بعد 10 دقائق.

</div>

```php
$bill = $client->createBill(
    billNo: 'Order-123-1',
    amount: '10.50',
    currency: Currency::Usd,
    note: 'Order 123',
);

header('Location: ' . $bill->paymentUrl);
```

<div dir="rtl">

وجّه الزبون إلى `paymentUrl` في متصفح النظام. عرض الويب المضمّن يعطل الرابط العميق لتطبيق ShamCash.

`Currency::Usd` هي `1` و `Currency::Syp` هي `2`. المبالغ سلاسل عشرية مثل `"10.50"`.

استخدم `billNo` جديدًا عندما يعيد الزبون المحاولة. لاحقة مثل `Order-123-1` و `Order-123-2` تتجنب رسالة «رقم الفاتورة موجود مسبقًا» للطلب نفسه.

## رابطا الاستدعاء وإعادة التوجيه

`callbackUrl` هو المكان الذي يرسل إليه ShamCash إشعار الويب المشفّر بطلب POST. اضبطه على العميل عندما تستخدم كل الفواتير نقطة النهاية نفسها.

`redirectUrl` هو المكان الذي يعود إليه متصفح المستخدم بعد الدفع. ShamCash لا يلحق `billNo`. ضع معرّف الطلب في هذا الرابط عندما تحتاجه صفحة العودة.

كلاهما اختياري على العميل واختياري في `createBill()`. لكل رابط، يستخدم SDK وسيطة `createBill()` عندما تُمرَّر، وإلا يستخدم قيمة العميل. ما زال ShamCash يطلب الرابطين داخل الحمولة المشفّرة. إذا بقي أي رابط فارغًا، يرمي `createBill()` الاستثناء `InvalidArgumentException` ولا يرسل شيئًا.

</div>

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

<div dir="rtl">

الفاتورة الأولى تستخدم القيمتين الافتراضيتين للعميل. الفاتورة الثانية تستبدل `callbackUrl` و `redirectUrl` لتلك الفاتورة فقط. احذف أي وسيطة للإبقاء على قيمة العميل. انظر [examples/create-bill.php](examples/create-bill.php).

## قراءة فاتورة

</div>

```php
$bill = $client->getBill('Order-123-1');
```

<div dir="rtl">

استدعِ هذا مرة واحدة، بعد 10 دقائق على الأقل من إنشاء الفاتورة، وفقط عندما لا يصل إشعار الويب. لا تستطلعها بشكل متكرر. انظر [examples/get-bill.php](examples/get-bill.php).

`$bill->status` يكون `BillStatus` عندما يرسل ShamCash معرّف حالة معروفًا: قيد الانتظار، أو استرداد، أو منتهية الصلاحية، أو مدفوعة، أو مستردّة جزئيًا. `$bill->raw` هو الكائن بعد فك الترميز، ويشمل أي حقل لم يربطه هذا SDK بعد.

## الاسترداد

مرّر مفتاح منع تكرار بطول يتراوح بين 10 و 100 حرفًا. إذا انتهت مهلة الطلب، أرسل المفتاح نفسه مرة أخرى. يعيد ShamCash الاسترداد الأصلي عندما يكون هذا المفتاح قد نجح مسبقًا، ويترك المفتاح غير مستخدم عندما يفشل الاسترداد.

</div>

```php
$refund = $client->refundBill(
    billNo: $bill->billNo,
    amount: '10.50',
    idempotencyKey: $idempotencyKey,
    note: 'Customer return',
);
```

<div dir="rtl">

يستخدم الاسترداد عملة الفاتورة. لا يجوز أن يتجاوز مجموع كل الاستردادات المبلغ المدفوع. انظر [examples/refund-bill.php](examples/refund-bill.php).

## الحركات

</div>

```php
$page = $client->listTransactions('2026-01-01', '2026-01-20', afterTranId: 0, limit: 1000);

foreach ($client->eachTransaction('2026-01-01', '2026-01-20', limit: 1000) as $transaction) {
    // $transaction->tranType يكون دفعة أو استردادًا عندما يكون معرّف النوع معروفًا.
}
```

<div dir="rtl">

يتبع `eachTransaction()` الحقل `hasMore` ويرسل `lastReturnedTranId` السابق بوصفه `afterTranId`. يجب أن يكون `limit` بين 10 و 2500. القيمة الافتراضية هي 500. انظر [examples/list-transactions.php](examples/list-transactions.php).

## إشعارات الويب

يرسل ShamCash طلب POST بالمحتوى `{ "encData": "..." }` إلى `callbackUrl` عندما تُدفع الفاتورة أو تنتهي صلاحيتها. مرّر ذلك المتن الخام إلى SDK. إعادة ترميز JSON قد تغيّر `encData` وتكسر فك التشفير.

</div>

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
    // $event->tranId هو معرّف دفعة ShamCash.
    // fulfillOrder($event->billNo, $event->tranId);
} elseif ($event->status === BillStatus::Expired) {
    // $event->tranId تكون null. لم يدفع الزبون خلال 10 دقائق.
    // releaseOrder($event->billNo);
}

http_response_code(200);
```

<div dir="rtl">

طبّق هذا التغيير مرة واحدة لكل `billNo` وحالة. يعيد ShamCash المحاولة عندما لا يتلقى HTTP 200 خلال 10 ثوانٍ، لذا يجب ألا يؤدي وصول الحدث نفسه مرة ثانية إلى تنفيذ الطلب من جديد. أعد 200 قبل العمل البطيء مثل إرسال البريد.

يرفض `parseWebhook()` الرمز الذي انتهت `exp` الخاصة به، أو الذي تكون `iat` الخاصة به بعيدة جدًا في المستقبل. الانحراف المسموح للساعة افتراضيًا هو 30 ثانية.

[examples/webhook.php](examples/webhook.php) هو نقطة النهاية الكاملة.

## الأخطاء

الأخطاء المحلية، مثل مبلغ غير صالح أو رابط مفقود، ترمي `InvalidArgumentException` قبل أي طلب HTTP.

إخفاقات ShamCash التجارية ما زالت تستخدم HTTP 200. ترمي `ApiException`. اقرأ `$exception->result` (`ResultCode`) و `$exception->resultCode`. أخطاء HTTP 400 و 404 و 415 و 500 والمهلات و JSON غير الصالح ترمي `TransportException`. الرمز غير الصالح يرمي `CryptoException`.

قد تُحفظ الفاتورة حتى لو سقطت استجابة الإنشاء. المحاولة التالية تعيد النتيجة `1704`. حمّل الفاتورة التي أنشأتها مسبقًا:

</div>

```php
use OkToCode\ShamCash\Enum\ResultCode;
use OkToCode\ShamCash\Exception\ApiException;

try {
    $bill = $client->createBill(billNo: $billNo, amount: '10.50', currency: Currency::Usd);
} catch (ApiException $exception) {
    if ($exception->result !== ResultCode::BillNoAlreadyExists) {
        throw $exception;
    }

    $bill = $client->getBill($billNo);
}
```

<div dir="rtl">

انظر [examples/retry-create-bill.php](examples/retry-create-bill.php).

لا يعيد SDK المحاولة. استدعاء `getBill()` و `listTransactions()` مرة أخرى آمن. أعد `refundBill()` فقط بمفتاح منع التكرار نفسه.

## التطوير

</div>

```bash
composer install
composer test
composer phpstan
composer cs
```
