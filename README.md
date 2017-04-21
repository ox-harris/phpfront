# PHPFront

PHPFront will help you read/write content on HTML templates from within your PHP application, read/write on attributes, traverse up/down the full document, manipulate - create, repeat, import, relocate, replace, remove - elements dynamically, produce a clean HTML output that represents your entire application. This is server-side rendering.

It implements the JQuery API with its powerful CSS3 selectors and chainable methods. It is well-tested and greatly optimized for use in websites and other PHP-based applications; built with love to bring all the ease and fun to your code, and a whole lot of new possibilities!

# Installation
## Requirement
  PHPFront requires a web server running PHP 5.3 or greater.
## Installation
  There are two options:
### With Composer
  ```composer
  composer require ox-harris/phpfront
  ```
### From Github

  Download from Github: [github.com/ox-harris/phpfront/releases](https://github.com/ox-harris/phpfront/releases)

Extract the PHPFront zip file and you’ll discover the most important folder for use named ‘lib’.
This folder contains the core working files. *This folder and its content are things you SHOULD NOT edit*.

Move the PHPFront folder to the frontend directory of your project or anywhere from the root directory of your project – depending on your application’s directory structure. Just make sure your application’s autoloader can pick up the PHPFront class when called – that’s if your project is bundled with an autoloader. Or simply note down the path to where you decide to put the PHPFront files so you can manually include this path during setup.

# Test
To see if PHPFront is available for use, use `PHPFront::info()`. This should show a few lines of info.
If you just want to test PHPFront or if your project is nothing more than basic, here is a test-case setup in numbered steps.
* Create a new php file named ‘app.php’ – just the name for this example.
* Copy the PHPFront folder to the same directory as the app.php file.
* Create a plain HTML page named ‘template.html’- one that contains no php tags – and put the file in this same directory.

Then in your app.php:
--------------
* Include the PHPFront class.
  
  ```php
  Include ‘PHPFront/lib/PHPFront.php’;

  // If you stored the PHPFront folder in a different location, your include path would change.
  // Where ‘path-to-PHPFront’ is your actual path to where you stored PHPFront
  Include ‘path-to-PHPFront/PHPFront/lib/PHPFront.php’;
  ```

* PHPFront is now available to our app.php script, so we instantiate it:
  ```php
  $PHPFront = new PHPFront;
  ```

* Now, we hand PHPFront the template to use - our template.html page
  ```php
  $PHPFront->setTemplate(‘template.html’);

  // If your stored template.html in a different location, your path would change.
  // Where ‘path-to-template is your actual path to where you stored template.html
  $PHPFront->setTemplate(‘path-to-template/template.html’);
  ```
    
* Now we can start reading and writing content on respective elements in the template with CSS3 selectors

  ```php
  // For document title (title)
  $PHPFront->find(‘title’)->html(‘This is document title’);

  // For page heading 1 (h1)
  $PHPFront->find(‘h1’)->html(‘Hello World!’);

  // For page paragraph (p)
  $PHPFront->find(‘p’)->html(‘Here is my first PHPFront project’)->addClass('rounded-corners')->css('color', 'gray');
  
  // Load a HTML fragment into a DIV and manipulate its content
  $PHPFront->find('#container')->load(__DIR__.'/templates/table.html')->find('tr:even')->css('background-color', 'whitesmoke')
  ->parents('table')->attr('id', 'employee-table')->append('<tr><td>342</td><td>John Doe</td></tr>');
  ```

* Finally, we render our page using PHPFront’s render() function
  ```php
  $PHPFront->render();
  ```
  
And that’s it! Preview your app.php in a browser and experience the PHPFront's simplicity and neatness first time on your project!

----------------

# Documentation
https://ox-harris.github.io/phpfront/documentation/ (FOR phpFront v1.0.0)

# Feedback
All bugs, feature requests, pull requests, feedback, etc., are welcome. [Create an issue](https://github.com/ox-harris/phpfront/issues).

# Follow Up
https://www.twitter.com/PHPFront.

Visit 
http://www.facebook.com/PeeHPFront

# Authors
  Oxford Harrison <ox_harris@yahoo.com>
  

# License
GPL-3.0 - See LICENSE
  

