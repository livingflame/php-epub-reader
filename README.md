# php-epub-reader

php-epub-reader is an online file viewer that allows you to view the ebooks stored in EPUB format.

This project does not require a database so you can just clone or download this repository and then move to your webhost of choice.

Please let me know if there are bugs, thank you!

![php-epub-reader](thumb.jpg)

## Features:

- Text to speech
- go to next page automatically when on text to speech
- Auto create book cover thumbnails when ebook does not have book cover
- Remember last chapter read

## Instructions

- Move all your epub files under books/ directory.
- Visit the main page. You should be able to see all the books you have added in the books directory
- If you don't see your books or not all books have been listed properly, go to tmp/cache/ directory and remove all files except .gitignore

## Requirements:

- Make sure that tidy is enabled in your php hosting. This will require editing php.ini to uncomment `extension=tidy`