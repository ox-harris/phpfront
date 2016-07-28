# PHPFront
*Well-Engineered for Templating. Uses no template syntaxes!* [ox-harris.github.io/phpfront](https://ox-harris.github.io/phpfront)

-----------------------------------------------

PHPFront is a fully-featured template engine for PHP.
It facilitates the globally-held standard of code separation in application building.
It helps you dynamically render application content on templates without mixing application codes (PHP) with presentation codes (HTML).

  *  This code separation brings about cleaner and more maintainable code - the same reason why you do not mix CSS styles with HTML.
  *  And on the critical side, you avoid all the security issues associated with using PHP codes on HTML templates.

Furthermore, PHPFront brings all the ease and fun to your code, and a whole lot of new possibilities!

**Compare PHPFront with Smarty and other Text-based Template Engines**
  
  *  No template syntaxes - not even one. PHPFront is DOM-based not text-based.
  *  Requires no proprietary syntaxes, PHP codes, or the .tpl extension on templates.
  *  Built around familiar standards and conventions: PHP, HTML, CSS, XPATH.

# Installation
## Requirement
  PHPFront requires a web server running PHP 5.3 or greater.
## Installation
  There are two options:
### With Composer
  ```composer
  composer require ox-harris/phpfront: ~1.0
  ```
### From Github

  [github.com/ox-harris/phpfront/releases](https://github.com/ox-harris/phpfront/releases)

**Folder Structure**

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
  
* Now we can start assigning content to the respective elements in the template using PHPFront’s assign() function

  The function accepts to parameters:
    - i	$element_selector 		string
    - ii $data 					string|array
  
  ```php
  // For document title (title)
  $PHPFront->assign(‘title’, ‘This is document title’);

  // For page heading 1 (h1)
  $PHPFront->assign(‘h1’, ‘Hello World!’);

  // For page paragraph (p)
  $PHPFront->assign(‘p’, ‘Here is my first PHPFront project’);
  ```
  
* Finally, we render our page using PHPFront’s render() function
  ```php
  $PHPFront->render();
  ```
  
And that’s it! Preview your app.php in a browser and experience the PHPFront's simplicity and neatness first time on your project!

----------------

# Documentation
https://ox-harris.github.io/phpfront/documentation/

# Follow Up
https://www.twitter.com/PHPFront.
Visit 
http://www.facebook.com/PHPFront

# Authors
  Oxford Harrison <ox_harris@yahoo.com>
  

# License
GPL-3.0 - See LICENSE
  

----------------

# Usage Comparison with Smarty

### Samrty - (adapted from smarty.net):

#### The php
  ```php
  include('Smarty.class.php');

  // create object
  $smarty = new Smarty;

  // assign some content. This would typically come from
  // a database or other source, but we'll use static
  // values for the purpose of this example.
  $smarty->assign('name', 'george smith');
  $smarty->assign('address', '45th & Harris');

  // display it
  $smarty->display('index.tpl');
  ```
  
#### The template - before

  ```html
  <html>
  <head>
  <title>Info</title>
  </head>
  <body>

	<pre>
	  User Information:
	  Name: {$name}
	  Address: {$address}
	</pre>

  </body>
  </html>
  ```
  
#### The template - after

```html
  <html>
  <head>
	<title>Info</title>
  </head>
  <body>
  
	<pre>
	  User Information:
	  Name: george smith
	  Address: 45th &amp; Harris
	</pre>

  </body>
  </html>
  ```
  
### PHPFront:

#### The php

  ```php
  include('PHPFront/lib/PHPFront.php');

  // create object
  $PHPFront = new PHPFront;

  // assign some content. This would typically come from
  // a database or other source, but we'll use static
  // values for the purpose of this example.
  $PHPFront->assign('#name::after', 'george smith');
  $PHPFront->assign('#address::after', '45th & Harris');

  // display it
  $PHPFront->setTemplate('index.html');
  $PHPFront->render();
  ```
  
#### The template - before

  ```html
  <html>
  <head>
	<title>Info</title>
  </head>
  <body>
  
	<pre>
	  User Information:
	  <span id="name">Name: </span>
	  <span id="address">Address: </span>
	</pre>

  </body>
  </html>
  ```
  
#### The template - after

  ```html
  <html>
  <head>
	<title>Info</title>
  </head>
  <body>
  
	<pre>
	  User Information:
	  <span id="name">Name: george smith</span>
	  <span id="address">Address: 45th &amp; Harris</span>
	</pre>

  </body>
  </html>
  ```
----------------
  
## The similarities
  * Instantiating with the 'new' keyword - same.
  * Assigning data to named elements in the markup - same.
  * Displaying - Smarty: `display()`; PHPFront: `render()`.

## The differences (lest you think they're the same all the way):

Smarty

  * A smarty template is not a standard HTML markup. But a mix of HTML and Smarty's own tags and syntaxes.
  * `Smarty::assign()` assigns data to template variables, and you pick up those variables on the template to manually render or loop over.
  * A Smarty template file has the file extension .tpl. not .html
  * You must learn PHP, HTML and Smarty syntaxes to work with Smarty.

PHPFront

  * Any valid HTML markup is a template! And valid HTML markup is valid anywhere - with or without the PHPFront Engine!
  * `PHPFront::assign()` assigns data directly to elements in a template. No extra overhead of editing the template using template syntaxes to render or loop over.
  * Template file extension is rightly .html
  * PHPFront requires no other language. (You've learned PHP and HTML already! And that's all! That's the standard.)
  Furthermore, if you know CSS, you can even target template elements by 
  	id (`$PHPFront->assign('#element', '...'))`, 
  	ClassName (`$PHPFront->assign('.element', '...'))`, 
  	Attribute (`$PHPFront->assign('element[attr]', '...'))`.
  And if you're a pro, find anything on the UI with xpath query: 
  	`$PHPFront->assign('xpath:parent/child', '...')`.

You should by now see the possibilities! See the [official documentation](https://ox-harris.github.io/phpfront/documentation/), and tutorials! 
