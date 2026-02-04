# TelegramPrivacyShield
**For Persian version, see [README_FA.md](README_FA.md).**
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

```bash
https://yourserver.com/index.php
```

### Commands
#### Private Chat
/start – Show bot menu and info
/sendto @GroupID Message – Send anonymous message to a group

#### Group
/getChatID – Get numeric ID of the group (the message will be deleted)
