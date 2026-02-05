<?php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '0');
// set_time_limit(0);       // زمان اجرای اسکریپت رو نامحدود می‌کنه
// ================== تنظیمات اولیه ==================
$token = "BOTTOKEN";  // توکن ربات که از @BotFather دریافت می‌شود
$botUsername = "USERNAME";  // نام کاربری ربات (بدون @)
$sourceUrl = "https://github.com/CyrusSource/TelegramPrivacyShield"; // آدرس سورس کد
$api = "https://api.telegram.org/bot$token/"; // آدرس API تلگرام

// دریافت و تبدیل داده‌های ورودی از تلگرام
$update = json_decode(file_get_contents("php://input"), true);

// ================== توابع کمکی ==================

/**
 * ارسال درخواست به API تلگرام
 * @param string $method نام متد API
 * @param array $data داده‌های ارسالی
 * @return string پاسخ سرور
 */
function request($method, $data = [])
{
    global $api;
    $ch = curl_init($api . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $data
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

/**
 * حذف پیام در گروه
 * @param int $chat_id آیدی چت
 * @param int $message_id آیدی پیام
 */
function deleteMessage($chat_id, $message_id)
{
    request("deleteMessage", [
        "chat_id" => $chat_id,
        "message_id" => $message_id
    ]);
}

/**
 * ارسال پیام متنی
 * @param int $chat_id آیدی چت
 * @param string $text متن پیام
 * @param int|null $reply آیدی پیام برای پاسخ
 * @param array|null $markup کیبورد یا اینلاین کیبورد
 */
function sendMessage($chat_id, $text, $reply = null, $markup = null)
{
    $data = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];
    if ($reply) $data["reply_to_message_id"] = $reply;
    if ($markup) $data["reply_markup"] = json_encode($markup);
    request("sendMessage", $data);
}

/**
 * ویرایش پیام متنی
 * @param int $chat_id آیدی چت
 * @param int $message_id آیدی پیام
 * @param string $text متن جدید
 * @param array|null $markup کیبورد جدید
 */
function editMessageText($chat_id, $message_id, $text, $markup = null)
{
    $data = [
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "text" => $text,
        "parse_mode" => "HTML"
    ];
    if ($markup) $data["reply_markup"] = json_encode($markup);
    request("editMessageText", $data);
}

/**
 * ارسال رسانه (عکس، ویدیو، استیکر و ...)
 * @param string $method نوع رسانه (photo, video, sticker و ...)
 * @param int $chat_id آیدی چت
 * @param string $file_id آیدی فایل در تلگرام
 * @param string|null $caption توضیح رسانه
 * @param int|null $reply آیدی پیام برای پاسخ
 */
function sendMedia($method, $chat_id, $file_id, $caption = null, $reply = null)
{
    $data = ["chat_id" => $chat_id];

    // تعیین پارامتر مناسب
    $paramMap = [
        "photo" => "photo",
        "video" => "video",
        "voice" => "voice",
        "audio" => "audio",
        "document" => "document"
    ];

    if (!isset($paramMap[$method])) return false;

    $data[$paramMap[$method]] = $file_id;

    // فقط برای رسانه‌هایی که caption می‌گیرند
    if ($caption && $method !== "voice") {
        $data["caption"] = $caption;
        $data["parse_mode"] = "HTML";
    }

    if ($reply) $data["reply_to_message_id"] = $reply;

    return request("send" . ucfirst($method), $data);
}

// ================== اطلاعات ربات ==================

/**
 * دریافت آیدی عددی ربات
 * @return int|null آیدی ربات
 */
function getBotId()
{
    $res = json_decode(request("getMe"), true);
    return $res["result"]["id"] ?? null;
}

// ================== اطلاعات کاربر ==================

/**
 * دریافت نام کامل کاربر
 * @param array $msg آرایه پیام
 * @return string نام کامل
 */
function getUserName($msg)
{
    $f = $msg["from"]["first_name"] ?? "";
    $l = $msg["from"]["last_name"] ?? "";
    return trim("$f $l");
}

/**
 * بررسی ادمین بودن کاربر در گروه
 * @param int $chat_id آیدی چت
 * @param int $user_id آیدی کاربر
 * @return bool true اگر ادمین باشد
 */
function isUserAdmin($chat_id, $user_id)
{
    $res = json_decode(request("getChatMember", [
        "chat_id" => $chat_id,
        "user_id" => $user_id
    ]), true);

    return in_array($res["result"]["status"] ?? "", ["administrator", "creator"]);
}

// ================== اطلاعات گروه ==================

/**
 * دریافت عنوان گروه
 * @param int $chat_id آیدی چت
 * @return string عنوان گروه
 */
function getGroupTitle($chat_id) {
    global $api;
    $res = json_decode(file_get_contents($api . "getChat?chat_id=" . $chat_id), true);
    return $res["result"]["title"] ?? "گروه شما";
}

// ================== چت خصوصی ==================

// بررسی اینکه پیام در چت خصوصی ارسال شده باشد
if ($update["message"]["chat"]["type"] === "private") {

    $chat_id = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    // دستور /start
    if ($text === "/start") {

        $keyboard = [
            "inline_keyboard" => [
                [[
                    "text" => "💻 سورس کد",
                    "url" => $sourceUrl
                ]],
                [[
                    "text" => "📘 راهنما",
                    "callback_data" => "help"
                ]],
                [[
                    "text" => "➕ افزودن به گروه",
                    "url" => "https://t.me/$botUsername?startgroup=true"
                ]]
            ]
        ];

        sendMessage(
            $chat_id,
            "🛡 <b>ربات محافظ حریم خصوصی کاربران</b>\n\n" .
            "این ربات تمام پیام‌های کاربران در گروه را بلافاصله حذف کرده و مجدداً " .
            "بدون نمایش هویت واقعی آن‌ها منتشر می‌کند.\n\n" .
            "• بدون ذخیره اطلاعات\n" .
            "• متن‌باز و قابل بررسی\n" .
            "• مناسب شرایط حساس\n\n" .
            "📌 تنها دسترسی موردنیاز ربات: <b>حذف پیام‌ها</b>",
            null,
            $keyboard
        );
        exit;
    }

    // دستور /sendto
    if (strpos($text, "/sendto") === 0) {

        $parts = explode(" ", $text, 3);

        if (count($parts) < 3 || empty($parts[1]) || empty($parts[2])) {
            sendMessage($chat_id, "❌ دستور نامعتبر است.\nفرمت صحیح:\n/sendto [@GroupID] [متن پیام]");
            exit;
        }

        $group = $parts[1];
        $message = $parts[2];
        $name = getUserName($update["message"]);

        $result = request("sendMessage", [
            "chat_id" => $group,
            "text" => "<blockquote>کاربر <b>$name</b>:</blockquote>\n$message",
            "parse_mode" => "HTML"
        ]);

        if (!$result || strpos($result, '"ok":false') !== false) {
            sendMessage($chat_id, "❌ امکان ارسال پیام وجود ندارد.\nربات در گروه حضور ندارد یا گروه عمومی نیست. در گروه خصوصی با آیدی عددی گروه می‌توانید از این قابلیت استفاده کنید. آیدی عددی گروه را از ادمین گروه بگیرید یا در گروه مورد نظر بنویسید <code>/getChatID</code>");
        } else {
            sendMessage($chat_id, "✅ پیام با موفقیت ارسال شد.");
        }
        exit;
    }

    exit;
}

// ================== پاسخ به Callback Query ==================

if (isset($update["callback_query"])) {

    $cb = $update["callback_query"];
    $chat_id = $cb["message"]["chat"]["id"];
    $message_id = $cb["message"]["message_id"];
    $data = $cb["data"];

    // راهنما
    if ($data === "help") {

        $keyboard = [
            "inline_keyboard" => [
                [[
                    "text" => "➕ افزودن به گروه",
                    "url" => "https://t.me/$botUsername?startgroup=true"
                ]],
                [[
                    "text" => "🔙 بازگشت",
                    "callback_data" => "back"
                ]]
            ]
        ];

        editMessageText(
            $chat_id,
            $message_id,
            "📘 <b>راهنمای ربات</b>\n\n" .
            "👤 <b>کاربران:</b>\n" .
            "• پیام‌ها بلافاصله حذف می‌شوند\n" .
            "• بدون نمایش هویت واقعی منتشر می‌شوند\n" .
            "• هیچ اطلاعاتی ذخیره نمی‌شود\n\n" .
            "• برای امنیت بیشتر در ربات به شکل زیر پیام بنویسید تا در گروه مورد نظر منتشر شود، امنیت کامل تنها در روش زیر است.\n\n" .
            "📤 <b>ارسال پیام ناشناس:</b>\n" .
            "<code>/sendto @GroupID متن پیام</code>\n\n" .
            "👮 <b>ادمین‌ها:</b>\n" .
            "• ربات را ادمین کنید\n" .
            "• میتوانید فقط دسترسی <b>حذف پیام‌ها</b> را فعال کنید" .
            "• برای استفاده بهتر و جلوگیری از اسپم، حالت آرام (Slow Mode) را فعال کنید.",
            $keyboard
        );
    }

    // بازگشت به صفحه اصلی
    if ($data === "back") {

        $keyboard = [
            "inline_keyboard" => [
                [[
                    "text" => "📘 راهنما",
                    "callback_data" => "help"
                ]],
                [[
                    "text" => "💻 سورس کد",
                    "url" => $sourceUrl
                ]],
                [[
                    "text" => "➕ افزودن به گروه",
                    "url" => "https://t.me/$botUsername?startgroup=true"
                ]]
            ]
        ];

        editMessageText(
            $chat_id,
            $message_id,
            "🛡 <b>ربات محافظ حریم خصوصی کاربران</b>\n\n" .
            "این ربات پیام‌های کاربران را در گروه حذف کرده و مجدداً بدون نمایش هویت واقعی منتشر می‌کند.\n\n" .
            "• بدون ذخیره اطلاعات\n" .
            "• متن‌باز و قابل بررسی\n" .
            "• مناسب شرایط حساس\n\n" .
            "📌 تنها دسترسی موردنیاز ربات: <b>حذف پیام‌ها</b>",
            $keyboard
        );
    }
    
    // پاسخ به Callback Query برای برداشتن ساعت از دکمه
    request("answerCallbackQuery", [
        "callback_query_id" => $cb["id"]
    ]);

    exit;
}

// ================== پردازش پیام در گروه ==================

if (!isset($update["message"])) exit;

$msg = $update["message"];
$chat_id = $msg["chat"]["id"];
$user_id = $msg["from"]["id"];
$message_id = $msg["message_id"];

// اگر پیام از ربات باشد، پردازش نکن
if ($msg["from"]["is_bot"] ?? false) exit;

// ===== بررسی ادمین بودن ربات هنگام اضافه شدن به گروه =====
if (isset($msg["new_chat_members"])) {
    $groupTitle = getGroupTitle($chat_id);
    foreach ($msg["new_chat_members"] as $member) {
        if ($member["id"] == getBotId()) {
 
            $res = json_decode(request("getChatMember", [
                "chat_id" => $chat_id,
                "user_id" => getBotId()
            ]), true);

            if (($res["result"]["status"] ?? "") !== "administrator") {
                sendMessage($chat_id, "⚠️ لطفاً ربات را ادمین کرده و دسترسی حذف پیام‌ها را بدهید تا بتواند کار کند. همچنین پیشنهاد می‌شود پیام بعدی را برای استفاده بهتر پین کنید.");
                
                // پیام خوش‌آمدگویی با ساختار استاندارد
                sendMessage($chat_id, "🛡 به گروه امن $groupTitle خوش آمدید!\n\n" .
                    "سلام! در این گروه امنیت و حریم خصوصی کاربر مهم‌ترین چیزه! در دوره‌ای که با داشتن آیدی و اکانت شما می‌تونن به شماره یا اطلاعات شما دسترسی داشته باشن، من به کار میام تا آزادی بیان رو به ارمغان بیارم!\n\n" .
                    "من ربات و واسطه‌ای هستم که حریم خصوصی کاربران رو کاملاً حفظ می‌کنم. در این گروه هر پیامی که ارسال کنید، بدون اینکه هویت شما مشخص بشه، در گروه تنها با نام شما منتشر می‌شه. این کار با حذف سریع پیام شما و بازنشر اون انجام میشه. من فقط یک واسطه امن هستم:\n\n" .
                    "• تمام پیام‌ها در گروه پاک می‌شن و دوباره ناشناس ارسال می‌شن.\n" .
                    "• برای امنیت بیشتر در پیام‌های حساس فقط و فقط از <b>/sendto</b> استفاده کنید. کاملا ناشناس و امن!" .
                    "📌 ارسال پیام کاملا ناشناس:\n" .
                    "اگر می‌خواید پیام‌ها بدون اینکه حتی ادمین بفهمه شما فرستنده هستید، ارسال بشن و هیچ ردی در Recent Actions هم نباشه، کافیه در چت خصوصی با ربات از دستور زیر استفاده کنید، ربات رو استارت کنید و بنویسید:\n\n" .
                    "<blockquote><code>/sendto $chat_id متن پیام شما</code></blockquote>\n\n" .
                    "• پیام شما با ارسال در گروه فقط در Recent Actions گروه قابل مشاهده است.\n" .
                    "• حتی می‌تونید جلوی نمایش اون هم با این سیستم بگیرید!\n\n" .
                    "• با این سیستم از هرگونه نشر اطلاعات در گروه در امان باشید و امنیت را به 100 برسانید!\n\n" .
                    "<blockquote expandable>⚙️ ویژگی‌های ربات برای ادمین‌ها:\n\n" .
                    "• ربات فقط به دسترسی حذف پیام‌ها نیاز دارد.\n" .
                    "• هیچ لاگ یا دسترسی به پیام‌ها در ربات وجود ندارد.\n\n" .
                    "• برای استفاده بهتر و جلوگیری از اسپم، حالت آرام (Slow Mode) را فعال کنید." .
                    "• پیشنهاد می‌شود فقط از ربات رسمی با آیدی [$botUsername] استفاده کنید تا امنیت کاربران حفظ شود. می‌توانید آیدی اصلی را از Github نیز بررسی کنید.\n" .
                    "• در گروه‌های خصوصی آیدی عددی گروه باید در دسترس باشد تا کاربر توانایی ارسال پیام به صورت امن را داشته باشد. این آیدی با ارسال پیام <code>/getChatID</code> در چت خصوصی شما در ربات دیده می‌شود.\n" .
                    "• تمام پیام‌ها پاک می‌شوند، امنیت و حریم خصوصی کاربران اولویت اصلی است، حتی پیام شما هم حذف می‌شود.\n" .
                    "• در صورت داشتن هرگونه پیشنهاد یا انتقاد به ربات و بخش راهنما مراجعه کنید.\n" .
                    "• سورس کد ربات متن‌باز و در دسترس است، می‌توانید بررسی کنید.\n\n" .
                    "سپاس از شما که به امنیت و حریم خصوصی کاربران اهمیت می‌دهید. از نصب ربات در گروه نیز تشکر می‌کنم. برای بهترین تجربه، لطفاً این پیام را پین کنید و مطمئن شوید ربات در گروه ادمین است.❤️</blockquote>");
                exit;
            }
        }
    }
}

// حذف پیام استارت در گروه (برای جلوگیری از اسپم)
if (($msg["text"] ?? "") === "/start@$botUsername") {
    deleteMessage($chat_id, $message_id);
    exit;
}
if (($msg["text"] ?? "") === "/start@$botUsername true") {
    deleteMessage($chat_id, $message_id);
    exit;
}

// ===== دریافت آیدی گروه =====
if (isset($msg["text"]) && trim($msg["text"]) === "/getChatID") {
    // پاک کردن پیام کاربر در گروه
    deleteMessage($chat_id, $message_id);
    
    $groupTitle = getGroupTitle($chat_id);

    // ارسال آیدی گروه به پیوی کاربر
    sendMessage($user_id, "🆔 آیدی عددی گروه:\n<b>$chat_id</b>\n\n" . "پیام رو اینطور میتونید در گزوه <b>$groupTitle</b> ارسال کنید:\n<blockquote><code>/sendto $chat_id متن پیام شما</code></blockquote>");

    // توقف پردازش بیشتر
    exit;
}

$name = getUserName($msg);
$reply = $msg["reply_to_message"]["message_id"] ?? null;

// حذف تمام پیام‌های کاربر
deleteMessage($chat_id, $message_id);

// ===== پردازش انواع پیام‌ها =====

// پیام متنی
if (isset($msg["text"])) {
    sendMessage($chat_id, "<blockquote>کاربر <b>$name</b>:</blockquote>\n" . $msg["text"], $reply);
    exit;
}

// استیکر
if (isset($msg["sticker"])) {
    sendMedia("sticker", $chat_id, $msg["sticker"]["file_id"], null, $reply);
    exit;
}

// گیف
if (isset($msg["animation"])) {
    sendMedia("animation", $chat_id, $msg["animation"]["file_id"], null, $reply);
    exit;
}

// نقشه انواع رسانه‌ها
$map = [
    "photo" => "تصویر",
    "video" => "ویدیو",
    "voice" => "ویس",
    "audio" => "صوت",
    "document" => "فایل"
];

// پردازش سایر انواع رسانه
foreach ($map as $key => $label) {
    if (isset($msg[$key])) {
        $file_id = is_array($msg[$key]) && isset($msg[$key]["file_id"])
                    ? $msg[$key]["file_id"]
                    : end($msg[$key])["file_id"] ?? null;

        if (!$file_id) continue; // اگر file_id موجود نبود رد شود

        $caption = "<blockquote>📎 $label ارسال شده توسط <b>$name</b></blockquote>";
        if (!empty($msg["caption"])) $caption .= "\n" . $msg["caption"];

        sendMedia($key, $chat_id, $file_id, $caption, $reply);
        exit;
    }
}
