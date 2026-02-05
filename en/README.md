# TelegramPrivacyShield
A Telegram bot that protects users' privacy by anonymously reposting messages in groups. Open-source and easy to deploy.

---

## English Version
TelegramPrivacyShield is a Telegram bot that protects users' privacy by remove user messages and anonymously reposting that messages in groups. Open-source and easy to deploy. Perfect for sensitive groups while keeping users' identities private.

### Features
- Automatically delete messages in groups
- Repost messages without revealing the sender
- No data storage
- Open-source and fully auditable
- Suitable for sensitive scenarios

### Installation
1. Create a bot with [BotFather](https://t.me/BotFather) and get the **token**.
2. Edit `index.php` with your **token** and **bot username**.
3. Add the bot to your group as **admin** and enable **Delete Messages** permission.
4. Set up the webhook pointing to your server:

```text
https://api.telegram.org/botTOKEN/setwebhook?url=DOMAIN
```

### Commands
#### Private Chat
- /start – Show bot menu and info
- /sendto @GroupID Message – Send anonymous message to a group

#### Group
- /getChatID – Get numeric ID of the group (the message will be deleted)

### Live Bot Preview & Evaluation
To explore the real behavior and features of the bot, you can interact with its active Telegram version:
- Bot ID:
https://t.me/neghabrobot
- Official CyrusCode Channel:
https://t.me/CyrusCode
- Testing & Evaluation Group:
https://t.me/+Z9YAwto3Ve1iZDk0

#### Important Notice
The currently active bot on Telegram is developed entirely in Persian (Farsi).
This GitHub repository does not provide a tested or live English version of the bot.
