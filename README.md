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

In you terminal, enter the following command:

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

### Run the App Locally

While in the project directory, open the PHP built-in web server:

```
php -S localhost:8888 -t public
```

The website should now be available on http://localhost:8888