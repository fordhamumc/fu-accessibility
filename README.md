# FU Accessibility
Adds accessibility helpers to the Wordpress admin. This plugin gives you the ability to:

* Filter the Media Library to only show images that do not have alt tags set. (Works in both the grid and list views)
* Prevent users from inserting an image into a post without an alt tag.
* Generate alt tags for images that do not already have them using the Microsoft Azure Computer Vision API

## Getting Started

### Requirements

* [Composer](https://getcomposer.org/)
* [NPM](https://www.npmjs.com/)

### Installation
After you clone the repo, install the dependencies.

```bash
composer install
npm install
```

### Start the watcher

```bash
npm run dev
```

### Build the plugin
Minifies the JavaScript and generate the language file

```bash
npm run build
```

## License
This project is licensed under the GPL v3.0 License - see the [LICENSE](LICENSE) file for details