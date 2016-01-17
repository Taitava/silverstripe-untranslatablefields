<?php

class UntranslatableFieldsExtension extends DataExtension
{
	/**
	 * Fields that will be made untranslatable (or translatable, if the invert mode is on).
	 * If false or an empty array, no fields a touched.
	 * Structure:
	 * array(
	 *        'MyClassName' => array(
	 *                'MyFieldName',
	 *                'MyOtherField',
	 *                'TitleField'
	 *        ),
	 *        'AnotherClassName' => array(
	 *                'SomeField',
	 *                'TitleField'
	 *        )
	 * )
	 * @const array|boolean fields
	 */
	const fields = false;

	/**
	 * If true, instead of untranslating all fields listed in the fields array, this module will untranslate all
	 * fields in all classes that are extended by this module, except those fields that are listed.
	 * @const boolean invert
	 */
	const invert = false;

	/**
	 * A list of CSS classes to be added to the FormField elements of untranslatable fields in the CMS. False if nothing
	 * should be added (default).
	 * @const boolean|array
	 */
	const add_classes_to_cms = false;

	/**
	 * A list of HTML attributes and their values to be added to the FormField elements of untranslatable fields in the
	 * CMS. False if nothing should be added (default).
	 * @const boolean|array
	 */
	const add_attributes_to_cms = false;

	/**
	 * Untranslate all fields in a DataObject that are marked to be untranslatable.
	 */
	public function onAfterWrite()
	{
		parent::onAfterWrite();
		if ($this->owner->UntranslatableFields_skipOnAfterWrite) return; //Another object has started the saving process, so don't create an infinite recursion loop
		$untranslatable_fields = $this->getUntranslatableFields();
		if (empty($untranslatable_fields)) return; //Should not translate this model at all (for some reason this extension has still been applied to this model, but there's nothing to do)
		foreach ($this->owner->getTranslations() as $translated_object)
		{
			$write = false;
			foreach ($untranslatable_fields as $field_name)
			{
				if ($translated_object->$field_name != $this->owner->$field_name)
				{
					$translated_object->$field_name = $this->owner->$field_name;
					$write                          = true;
				}
			}
			if ($write)
			{
				$do_publish = $translated_object->hasMethod('Published') && $translated_object->Published();
				if ($do_publish) die();
				$translated_object->UntranslatableFields_skipOnAfterWrite = true;
				$translated_object->write();
				unset($translated_object->UntranslatableFields_skipOnAfterWrite);
				#if ($do_publish) $translated_object->Publish(); //If an object was published before this modification, publish it again in order to make the changes public.
			}
		}
	}



	/**
	 * Returns an array of string names of the fields of this object that are marked as 'untranslatable'. If
	 * invert mode is on, it will obviously still return the list of untranslatable fields - this time it won't
	 * list the fields declared in the YAML config, but instead all fields except the ones listed in YAML config -
	 * due to the invert mode. So basically it always tells what will be untranslated and can be used to debug if
	 * problems arise.
	 * @return array
	 */
	public function getUntranslatableFields()
	{
		$config_fields = Config::inst()->get('UntranslatableFields', 'fields');
		$config_invert = Config::inst()->get('UntranslatableFields', 'invert');
		if (!isset($config_fields[$this->owner->ClassName])) return array(); //Class is not in the fields
		$class_fields = $config_fields[$this->owner->ClassName];
		if ($config_invert)
		{
			$fields = array();
			foreach (array_keys((array) $this->owner) as $field)
			{
				if (!in_array($field, $class_fields))
				{
					$fields[] = $field;
				}
			}
			return $fields;
		}
		return $class_fields;
	}

	public function updateCMSFields(FieldList $fields)
	{
		parent::updateCMSFields($fields);
		$add_classes_to_cms	= (array) Config::inst()->get('UntranslatableFields', 'add_classes_to_cms');
		$add_attributes_to_cms	= (array) Config::inst()->get('UntranslatableFields', 'add_attributes_to_cms');
		if (!$add_attributes_to_cms && !$add_classes_to_cms) return;
		$untranslatable_fields	= $this->getUntranslatableFields();
		foreach ($fields->dataFields() as &$field)
		{
			if (in_array($field->Name, $untranslatable_fields))
			{
				//Mark in the CMS that this field is untranslatable
				if (!empty($add_classes_to_cms)) foreach ($add_classes_to_cms as $class) $field->addExtraClass($class);
				if (!empty($add_attributes_to_cms)) foreach ($add_attributes_to_cms as $attribute => $value) $field->setAttribute($attribute, $value);
			}
		}
	}
}