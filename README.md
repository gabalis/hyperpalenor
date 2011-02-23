
HyperPalenor, a plugin for MODx Evolution
=========================================

Redirects to the new URL of moved and renamed documents. 

Currently for MODx Evolution only ( versions <2 )


How to install
--------------

* Create a new plugin in the MODx Manager ( Elements > Manage elements > Plugins > New plugin )

* Plugin name should be 'HyperPalenor'

* Copy the code from the included file 'hyperpalenor-plugin.php' to the 'Plugin code (php)' area

* Delete the first line of the pasted code where it says '<?php'

* Open the Configuration tab and paste the following in the 'Plugin configuration' area : 

&lang=Plugin language;text;en
&tablePrefix=Plugin table prefix;text;

* Open the System events tab and check the following events : 
-- OnBeforeDocFormSave
-- OnPageNotFound
-- OnDocFormRender


How to use
----------

You don't need to do anything, MODx cares about everything once the plugin is installed. 

Advanced options
----------------

While editing any document in the site tree a new HyperPalenor area appears under the Resource content area and Template Variables. The area displays URLs which when called would redirect to the current Document. You can add New redirection URLs by hand, but MODx will also do the work automatically : when you rename or move a document, its previous URL will be added to the list of Redirected URLs. 

To remove Redirect URLs, uncheck them and save the Document. 