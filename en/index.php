<?php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '0');

// ================== Initial Settings ==================
$token = "BOTTOKEN";  // Bot token received from @BotFather
$botUsername = "USERNAME";  // Bot username (without @)
$sourceUrl = "https://github.com/CyrusSource/TelegramPrivacyShield"; // Source code URL
$api = "https://api.telegram.org/bot$token/"; // Telegram API URL

// Receive and convert input data from Telegram
$update = json_decode(file_get_contents("php://input"), true);

// ================== Helper Functions ==================

/**
 * Send request to Telegram API
 * @param string $method API method name
 * @param array $data data to send
 * @return string server response
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
 * Delete message in group
 * @param int $chat_id chat id
 * @param int $message_id message id
 */
function deleteMessage($chat_id, $message_id)
{
    request("deleteMessage", [
        "chat_id" => $chat_id,
        "message_id" => $message_id
    ]);
}

/**
 * Send text message
 * @param int $chat_id chat id
 * @param string $text message text
 * @param int|null $reply message id for reply
 * @param array|null $markup keyboard or inline keyboard
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
 * Edit text message
 * @param int $chat_id chat id
 * @param int $message_id message id
 * @param string $text new text
 * @param array|null $markup new keyboard
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
 * Send media (photo, video, sticker, etc.)
 * @param string $method media type (photo, video, sticker, etc.)
 * @param int $chat_id chat id
 * @param string $file_id file id in Telegram
 * @param string|null $caption media caption
 * @param int|null $reply message id for reply
 */
function sendMedia($method, $chat_id, $file_id, $caption = null, $reply = null)
{
    $data = [
        "chat_id" => $chat_id,
        $method => $file_id
    ];
    if ($caption) {
        $data["caption"] = $caption;
        $data["parse_mode"] = "HTML";
    }
    if ($reply) $data["reply_to_message_id"] = $reply;
    request("send" . ucfirst($method), $data);
}

// ================== Bot Information ==================

/**
 * Get bot numeric ID
 * @return int|null bot id
 */
function getBotId()
{
    $res = json_decode(request("getMe"), true);
    return $res["result"]["id"] ?? null;
}

// ================== User Information ==================

/**
 * Get user's full name
 * @param array $msg message array
 * @return string full name
 */
function getUserName($msg)
{
    $f = $msg["from"]["first_name"] ?? "";
    $l = $msg["from"]["last_name"] ?? "";
    return trim("$f $l");
}

/**
 * Check if user is admin in group
 * @param int $chat_id chat id
 * @param int $user_id user id
 * @return bool true if admin
 */
function isUserAdmin($chat_id, $user_id)
{
    $res = json_decode(request("getChatMember", [
        "chat_id" => $chat_id,
        "user_id" => $user_id
    ]), true);

    return in_array($res["result"]["status"] ?? "", ["administrator", "creator"]);
}

// ================== Group Information ==================

/**
 * Get group title
 * @param int $chat_id chat id
 * @return string group title
 */
function getGroupTitle($chat_id) {
    global $api;
    $res = json_decode(file_get_contents($api . "getChat?chat_id=" . $chat_id), true);
    return $res["result"]["title"] ?? "Your Group";
}

// ================== Private Chat ==================

// Check if message is sent in private chat
if ($update["message"]["chat"]["type"] === "private") {

    $chat_id = $update["message"]["chat"]["id"];
    $text = trim($update["message"]["text"] ?? "");

    // /start command
    if ($text === "/start") {

        $keyboard = [
            "inline_keyboard" => [
                [[
                    "text" => "💻 Source Code",
                    "url" => $sourceUrl
                ]],
                [[
                    "text" => "📘 Help",
                    "callback_data" => "help"
                ]],
                [[
                    "text" => "➕ Add to Group",
                    "url" => "https://t.me/$botUsername?startgroup=true"
                ]]
            ]
        ];

        sendMessage(
            $chat_id,
            "🛡 <b>User Privacy Protection Bot</b>\n\n" .
            "This bot immediately deletes all user messages in the group and republishes them without showing their real identity.\n\n" .
            "• No data storage\n" .
            "• Open source and reviewable\n" .
            "• Suitable for sensitive situations\n\n" .
            "📌 The only required permission for the bot: <b>Delete Messages</b>",
            null,
            $keyboard
        );
        exit;
    }

    // /sendto command
    if (strpos($text, "/sendto") === 0) {

        $parts = explode(" ", $text, 3);

        if (count($parts) < 3 || empty($parts[1]) || empty($parts[2])) {
            sendMessage($chat_id, "❌ Invalid command.\nCorrect format:\n/sendto [@GroupID] [Message Text]");
            exit;
        }

        $group = $parts[1];
        $message = $parts[2];
        $name = getUserName($update["message"]);

        $result = request("sendMessage", [
            "chat_id" => $group,
            "text" => "<blockquote>User <b>$name</b>:</blockquote>\n$message",
            "parse_mode" => "HTML"
        ]);

        if (!$result || strpos($result, '"ok":false') !== false) {
            sendMessage($chat_id, "❌ Unable to send message.\nThe bot is not in the group or the group is not public. In private groups with numeric group ID you can use this feature. Get the numeric group ID from the group admin or write <code>/getChatID</code> in the desired group.");
        } else {
            sendMessage($chat_id, "✅ Message sent successfully.");
        }
        exit;
    }

    exit;
}

// ================== Callback Query Response ==================

if (isset($update["callback_query"])) {

    $cb = $update["callback_query"];
    $chat_id = $cb["message"]["chat"]["id"];
    $message_id = $cb["message"]["message_id"];
    $data = $cb["data"];

    // Help
    if ($data === "help") {

        $keyboard = [
            "inline_keyboard" => [
                [[
                    "text" => "➕ Add to Group",
                    "url" => "https://t.me/$botUsername?startgroup=true"
                ]],
                [[
                    "text" => "🔙 Back",
                    "callback_data" => "back"
                ]]
            ]
        ];

        editMessageText(
            $chat_id,
            $message_id,
            "📘 <b>Bot Help Guide</b>\n\n" .
            "👤 <b>Users:</b>\n" .
            "• Messages are immediately deleted\n" .
            "• Published without showing real identity\n" .
            "• No information is stored\n\n" .
            "📤 <b>Send Anonymous Message:</b>\n" .
            "<code>/sendto @GroupID message text</code>\n\n" .
            "👮 <b>Admins:</b>\n" .
            "• Make the bot an admin\n" .
            "• Only enable <b>Delete Messages</b> permission",
            $keyboard
        );
    }

    // Back to main page
    if ($data === "back") {

        $keyboard = [
            "inline_keyboard" => [
                [[
                    "text" => "📘 Help",
                    "callback_data" => "help"
                ]],
                [[
                    "text" => "💻 Source Code",
                    "url" => $sourceUrl
                ]],
                [[
                    "text" => "➕ Add to Group",
                    "url" => "https://t.me/$botUsername?startgroup=true"
                ]]
            ]
        ];

        editMessageText(
            $chat_id,
            $message_id,
            "🛡 <b>User Privacy Protection Bot</b>\n\n" .
            "This bot deletes user messages in the group and republishes them without showing their real identity.\n\n" .
            "• No data storage\n" .
            "• Open source and reviewable\n" .
            "• Suitable for sensitive situations\n\n" .
            "📌 The only required permission for the bot: <b>Delete Messages</b>",
            $keyboard
        );
    }
    
    // Answer Callback Query to remove hour from button
    request("answerCallbackQuery", [
        "callback_query_id" => $cb["id"]
    ]);

    exit;
}

// ================== Process Message in Group ==================

if (!isset($update["message"])) exit;

$msg = $update["message"];
$chat_id = $msg["chat"]["id"];
$user_id = $msg["from"]["id"];
$message_id = $msg["message_id"];

// If message is from a bot, don't process
if ($msg["from"]["is_bot"] ?? false) exit;

// ===== Check if bot is admin when added to group =====
if (isset($msg["new_chat_members"])) {
    $groupTitle = getGroupTitle($chat_id);
    foreach ($msg["new_chat_members"] as $member) {
        if ($member["id"] == getBotId()) {
 
            $res = json_decode(request("getChatMember", [
                "chat_id" => $chat_id,
                "user_id" => getBotId()
            ]), true);

            if (($res["result"]["status"] ?? "") !== "administrator") {
                sendMessage($chat_id, "⚠️ Please make the bot an admin and give it delete message permission so it can work. Also it's suggested to pin the next message for better use.");
                
                // Welcome message with standard structure
                sendMessage($chat_id, "🛡 Welcome to the secure group $groupTitle!\n\n" .
                    "Hello! In this group, security and user privacy are the most important things! In an era where having your ID and account can give access to your number or information, I come to bring freedom of expression!\n\n" .
                    "I am a bot and intermediary that completely protects user privacy. In this group, every message you send will be published in the group only with your name without revealing your identity. This is done by quickly deleting your message and republishing it. I am just a secure intermediary:\n\n" .
                    "• All messages in the group are deleted and republished anonymously.\n" .
                    "• I have no logs or access to your messages.\n\n" .
                    "📌 Send Anonymous Message:\n" .
                    "If you want messages to be sent without even the admin knowing you are the sender, and without any trace in Recent Actions, just use the following command in private chat with the bot, start the bot and write:\n\n" .
                    "<blockquote><code>/sendto $chat_id your message text</code></blockquote>\n\n" .
                    "• Your message is only visible in the group's Recent Actions when sent to the group.\n" .
                    "• You can even prevent its display with this system!\n\n" .
                    "<blockquote expandable>⚙️ Bot features for admins:\n\n" .
                    "• The bot only needs delete message permission.\n" .
                    "• It's suggested to only use the official bot with ID [$botUsername] to maintain user security. You can check the main ID from Github too.\n" .
                    "• The group numeric ID should be available so users can send messages securely. This ID is visible by sending <code>/getChatID</code> in your private chat with the bot.\n" .
                    "• All messages are deleted, user security and privacy are the main priority, even your message will be deleted.\n" .
                    "• For any suggestions or criticisms, refer to the bot and help section.\n" .
                    "• The bot source code is open source and available, you can review it.\n\n" .
                    "Thank you for caring about user security and privacy. Also thank you for installing the bot in the group. For the best experience, please pin this message and make sure the bot is admin in the group.❤️</blockquote>");
                exit;
            }
        }
    }
}

// Delete start message in group (to prevent spam)
if (($msg["text"] ?? "") === "/start@$botUsername") {
    deleteMessage($chat_id, $message_id);
    exit;
}
if (($msg["text"] ?? "") === "/start@$botUsername true") {
    deleteMessage($chat_id, $message_id);
    exit;
}

// ===== Get group ID =====
if (isset($msg["text"]) && trim($msg["text"]) === "/getChatID") {
    // Delete user's message in group
    deleteMessage($chat_id, $message_id);
    
    $groupTitle = getGroupTitle($chat_id);

    // Send group ID to user's private chat
    sendMessage($user_id, "🆔 Group Numeric ID:\n<b>$chat_id</b>\n\n" . "You can send message to group <b>$groupTitle</b> like this:\n<blockquote><code>/sendto $chat_id your message text</code></blockquote>");

    // Stop further processing
    exit;
}

$name = getUserName($msg);
$reply = $msg["reply_to_message"]["message_id"] ?? null;

// Delete all user messages
deleteMessage($chat_id, $message_id);

// ===== Process Different Message Types =====

// Text message
if (isset($msg["text"])) {
    sendMessage($chat_id, "<blockquote>User <b>$name</b>:</blockquote>\n" . $msg["text"], $reply);
    exit;
}

// Sticker
if (isset($msg["sticker"])) {
    sendMedia("sticker", $chat_id, $msg["sticker"]["file_id"], null, $reply);
    exit;
}

// Media type mapping
$map = [
    "photo" => "Image",
    "video" => "Video",
    "voice" => "Voice",
    "audio" => "Audio",
    "document" => "File",
    "animation" => "GIF"
];

// Process other media types
foreach ($map as $key => $label) {
    if (isset($msg[$key])) {
        $file = is_array($msg[$key]) ? end($msg[$key])["file_id"] : $msg[$key]["file_id"];
        $caption = "📎 $label sent by <b>$name</b>";
        if (!empty($msg["caption"])) $caption .= "\n" . $msg["caption"];
        sendMedia($key, $chat_id, $file, $caption, $reply);
        exit;
    }
}
