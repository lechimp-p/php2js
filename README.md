[![Build Status](https://api.travis-ci.com/lechimp-p/php2js.svg?branch=master)](https://travis-ci.com/lechimp-p/php2js)

# php2js - Compiles sane PHP to JavaScript

**php2js makes it possible that you write client side scripts in your favorite
scripting language.**

*This currently is in a proof of concept stage and in now way ready for prime time.*

## Usage

*Currently this only supports enough functionality to make the HelloWorld-example
happen.*

* Write your client-side script in PHP. Your main file is supposed to contain one
class implementing `JS\Script`. Dependencies to JS-API can be declared in the
constructor of said class and will be passed by the runtime.
* Compile your script using `php php2js.php compile $SCRIPT`. This will output some
js that you can include to your page. The compiler will resolve internal dependencies
and use a composer.json to pull in further files.
* Since this is not supposed to support every PHP-API and language construct, the
compiler may yell at you if you use some stuff it does not want to compile. Reasonable
and sane modern PHP should compile.

## Rationale

*Again, this currently is a proof of concept.*

When working on a (possibly old) PHP-applications users typically expect features
that need to be implemented client-side nowadays. This challenges communities of
PHP-developers in two different ways.

On the one hand, client-side demands new skills and knowledge. While we may expect
every contemporay PHP-developer to know its share of jquery, more complex applications
demand for means that jquery never meant to provide. When switching to modern
js-frameworks, like React or Vue, to implement requirements of these more complex
apps, we either demand PHP-developers to acquire the skills and knowledge to use
these tools or we need to add new developers to our projects that already have
these skills. In both scenarios we will add new layers of complexity to our
project.

On the other hand, PHP-projects will already have some code which possibly would
need to be rewritten for the client-side. If, e.g., one would want to perform
some validation on input on the client-side to provide quick feedback to users,
the according code would need to be rewritten in javascript (or another language)
and server- and client-side validation would need to be hold in sync afterwards.
This will be a handicap for every PHP-project compared to projects written in
language that work on client- and server-side. After all, two languages and tool
chains need to be supported.

**php2js** attempts to solve these problems by providing a way to write client-side
code in PHP, just as server-side code. Client-side code thus can be subject to
the same procedures (e.g. testing or static analysis) like server-side code and
can be compiled to JavaScript before deployment.

While doing this, **php2js** does not attempt to implement each and every feature
or API of PHP, thus being able to compile to JavaScript directly instead of
providing a PHP-runtime in JavaScript or figuring out (e.g.) how file-operations
can be emulated in the browser.

## Outlook

*We are not there yet, this is a proof of concept!!1*

The idea for this compiler was born when working on the UI-framework of the
[ILIAS Open Source LMS](https://www.ilias.de) ([on GitHub](https://www.github.com/ILIAS-eLearning/ILIAS")).
This proof of concept will be used in discussions in the community how to proceed
with the client-side functionality of the ILIAS-UI-framework. If this is ever put
to real work, we most probably will add another layer over the bare JS-APIs
implemented by this compiler that allow to add client-side functionality to the
ILIAS-UI-framework in a structured way. This compiler thus is intended to be
complemented by a client-side-framework written in PHP.
