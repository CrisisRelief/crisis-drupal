Webform Content Creator 8.x-1.x-dev

### About this module

- This module provides the ability to create nodes after submitting webforms, and do mappings 
  between the fields of the created node and webform submission values.

### Goals

- Creation of nodes after submitting a Webform;
- Possibility to map between Webform submission values and Node entity fields; 
- Possibility to save only some Webform Submission values in Drupal platform, after sending entire
  Webform submission by web service (REST/SOAP) to another party.

  
### Create a Webform Content Creator entity

1. Enable Webform Content Creator module;
2. Go to Webform Content Creator configuration page; (/admin/config/webform_content_creator)
3. Click on "Add configuration";
4. Give a title to Webform Content Creator entity and choose a Webform and a Content Type, in order to
   have mappings between Webform submission values and node field values, and then click on "Save";
5. In the configuration page (/admin/config/webform_content_creator), click on "Manage fields"
   on the entity you have just created;
6. In the "Title" input, you can give a title to the node that is created after submitting the Webform (tokens
   may be used);
7. After that, you have the possibility to choose the Node fields used in the mapping;
8. When choosing a Node field (checkbox on the left side of each field name), a Webform field can 
   be choosen to match with this Node field (optionally, you can provide a custom text instead, using 
   available tokens).
   
### New features in version 8.x-1.2

- Change custom textfield to custom textarea (https://www.drupal.org/project/webform_content_creator/issues/3089198)
- Add possibility to synchronize webform submission and the corresponding node. When this webform submission is edited 
  or deleted, the corresponding node is edited or deleted accordingly. To do this, you have to create a synchronization
  field in the content type and map this field with submission id.

