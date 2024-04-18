<img src="./public/img/coffee-color.png" alt="coffee cup" width="100"/>

My website for the mvc course
==========================

A website created using Symfony and Twig.

How to get the app running
--------------------------

### Prerequisites

Before you begin, make sure you have the following installed:

- PHP (>=8.x)
- Composer

### Clone the Repository

In your terminal, enter the following command:

```
git clone https://github.com/RunningStrings/mvc-report
```

### Install Dependencies

Move to the project directory, and install dependencies by entering the following commands:

```
composer install
npm install
```

### Compile Assets

While in the project directory, enter the following command to compile assets:

```
npm run build
```

### Watching for Changes

During development, watch for changes to assets and recompile automatically by entering the following command:

```
npm run watch
```

### How to make certain assets work

Due to certain unforeseen incompatibilities, in order to make background images and Fontawesome icons function correctly: After using 'npm run build' or 'npm run watch', edit the generated file app.css in public/build/ by therein removing all instances of

```
build/
```
then save the file, and refresh the app on your local server, or upload to live server. Background images and Fontawesome icons should now load as expected.

### Run the App Locally

While in the project directory, open the PHP built-in web server:

```
php -S localhost:8888 -t public
```

The website should now be available on http://localhost:8888