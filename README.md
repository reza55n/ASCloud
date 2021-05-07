A personal notetaking, cloud storage and file sharing service that you can put it on your own host. Sine it's on your own host, it won't need any registration or authorizations and also uploaded file's URL will be like original file's name! (With some exceptions like replacing `#%?&` characters with `_`)

It looks like a personal chat (especially 'Saved Messages' in Telegram) except it needs no installations or phone-based logins and also you can preview or share its files using a simple link.

This storage is relied on simplicity more than security, so it's possible that other people guess simple uploaded files' name and download them. If you don't want to share files, you can use more complex file names to prevent this problem.

![Alvandsoft Cloud screenshot](https://www.alvandsoft.com/cloud123/ascloud_0.1.png)

## Installation
1. Copy files in `public_html` or in a subdirectory
2. Run for first time to create `user.php` file

## Tips
You can increase `upload_max_filesize` from PHP configuration to allow large file uploads.

If you forgot your password, you can remove `user.php` file manually.

