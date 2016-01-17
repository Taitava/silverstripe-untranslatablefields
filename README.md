# Untranslatable Fields

## Maintainer

Jarkko Linnanvirta (Nickname: Taitava)
posti (at) taitavasti.fi
 
## Introduction

This module makes it possible to define fields that cannot be translated to different languages when using the
Translatable module. For example, you may have a model called Person with two fields: name and description. You have
installed the Translatable module in order to be able to provide the description field in different languages. But since
a person's name is not (usually) translated, there is no reason to have to enter the person's name multiple times - once
per every translation.

In practice, every time an object (that extends this module) gets saved to the database, its translations are iterated
over and the values of fields marked as untranslatable are copied over to the other translations and saved.

 * Make edits to whatever language version - edits to untranslatable fields are applied to all translations
 * Only the writing process is modified
 * Same values in untranslatable fields in the database across different translations
 * Not dependent on the CMS module (I believe - have not tested :) )
 * Untranslatable fields can have special CSS styles and HTML attributes in the backend (by default there are no style/
   attribute changes)
 
## Requirements

 * SilverStripe 3.1
 * Translatable
 
The CMS module is not necessarily needed.
 
## Installation

This is a very simple one to configure. But first, either download this module and unpack it to a folder named
"untranslatablefields" under your site's root directory, or install via Composer:

```bash
composer require "taitava/silverstripe-untranslatablefields:*"
```

Then create a new file: mysite/_config/untranslatablefields.yml and put the following content there:

```YAML
---
UntranslatableFields:
  fields:
    MyClass:
      - MyField
      - CommonField
    AnotherClass
      - AnotherField
      - CommonField
```

Also, remember to extend your classes with this module. Edit mysite/_config/config.yml and add the following lines:

```YAML
MyClass:
  extensions:
    - UntranslatableFieldsExtension
    
AnotherClass:
  extensions:
    - UntranslatableFieldsExtension
```

These two configurations together render fields MyField and CommonField in MyClass and fields AnotherField and
CommonField untranslatable. Note that specifying the classes does not affect any child classes!

**You can also do the opposite**: Untranslate all but the specified fields! Just add `invert: true` to
mysite/_config/untranslatablefields.yml:

```YAML
---
UntranslatableFields:
  fields:
    MyClass:
      - MyField
      - CommonField
    AnotherClass
      - AnotherField
      - CommonField
  invert: true
```

## Auto Publish

If the user saves and publishes for example a Page object, its different language versions will get saved, but not
published by default. This is because the DataObject::write() method does not publish changes by default.
UntranslatableFields can be set to do the publishing for DataObjects that has the Versioned extension.

This feature is off by default. To turn it on, add the following to mysite/_config/untranslatablefields.yml:

```YAML
UnstranslatableFields:
  auto_publish: true
```

Note: if the translation was in the Stage mode (= not published) before the write operation, it won't get published
automatically even if this option is set to true. If there were an oldder published version, the changes are written to
the newest (Staged) version and will not get published.


## Indicate the untranslatable status of fields in the CMS

There are a couple of low level ways to give the CMS users a hint about untranslatable fields. This should also work in
the general backend even without the CMS module and in the ModelAdmin of DataObjects.

You can add some custom CSS classes to the FormField elemnts of all untranslatable fields. Add this to
mysite/_config/untranslatablefields.yml:

```YAML
UntranslatableFields:
  add_classes_to_cms:
   - untranslatable
   - 'another-class'
```

You can also set HTML attributes in the same way:

```YAML
UntranslatableFields:
  add_attributes_to_cms:
   - data-untranslatable: yes
   - title: "This field can't be translated separately. Changes to this field are saved across all existing translations."
```

Just for you to know: if you are untranslating fields that has no CMS fields, this feature won't interfere with them: the
module only tries to add these classees/attributes to those fields that exist in the CMS/ModelAdmin editor.


## Notes

 * Class names and field names are case sensitive
 * What else did I have in my mind? I forgot :(

## Dreams

Stuff that *could* be done:

 * Checking class inheritance: no need to specify child classes separately. Also implement star prefix (*Class) in order to target a class exclusively.
 * Global untranslatable fields: untranslate these fields in all classes that extend this module