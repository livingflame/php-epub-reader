# php-epub-reader

![php-epub-reader](thumb.jpg)

Features:

- Text to speech
- go to next page automatically when on text to speech
- Auto create book cover thumbnails when ebook does not have book cover
- Remember last chapter read

This project does not require a database so you can just clone or download this repository and then move to your webhost of choice.

- Move all your epub files under books/ directory.
- Visit the main page. If you don't see your books or not all books have been listed properly, go to cache/ directory and remove all php files inside.

Requirements:

- Make sure that tidy is enabled in your php hosting. This will require editing php.ini to uncomment `extension=tidy`

Please let me know if there are bugs, thank you!