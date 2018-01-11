Subscription
============
This UIHook-Plugin allows you to subscribe multiple users to a course at once, by writing the names into a list. You can also type in e-mail addresses, to which the plugin will send an invitation and a formular to create an ilias account. 

Have a look at the [full documentation](/doc/Documentation.pdf?raw=true)

## Installation
This Plugin requires ActiveRecord and ilRouterGUI. In ILIAS 5.0 and above, both components are already installed. 
### Router
In ILIAS 4.4 the Subscription-Plugin needs a Router-Service to work. Please install the Service first:
 
You start in your ILIAS root directory

```bash
cd Services  
git clone https://github.com/studer-raimann/RouterService.git Router  
```
Switch to the setup-Menu of your Installation and perform a Structure-reload in the Tab Tools. this can take a few moments. After the reload has been performed, you can install the plugin.
### ActiveRecord
ILIAS 4.4 does not include ActiveRecord. Therefore please install the latest Version of active record before you install the plugin:
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Libraries/  
cd Customizing/global/plugins/Libraries  
git clone https://github.com/studer-raimann/ActiveRecord.git  
```
### Plugin
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/  
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/  
git clone https://github.com/studer-raimann/Subscription.git  
```
As ILIAS administrator go to "Administration->Plugins" and install/activate the plugin.

### Hinweis Plugin-Patenschaft
Grundsätzlich veröffentlichen wir unsere Plugins (Extensions, Add-Ons), weil wir sie für alle Community-Mitglieder zugänglich machen möchten. Auch diese Extension wird der ILIAS Community durch die studer + raimann ag als open source zur Verfügung gestellt. Diese Plugin hat noch keinen Plugin-Paten. Das bedeutet, dass die studer + raimann ag etwaige Fehlerbehebungen, Supportanfragen oder die Release-Pflege lediglich für Kunden mit entsprechendem Hosting-/Wartungsvertrag leistet. Falls Sie nicht zu unseren Hosting-Kunden gehören, bitten wir Sie um Verständnis, dass wir leider weder kostenlosen Support noch Release-Pflege für Sie garantieren können.

Sind Sie interessiert an einer Plugin-Patenschaft (https://studer-raimann.ch/produkte/ilias-plugins/plugin-patenschaften/ ) Rufen Sie uns an oder senden Sie uns eine E-Mail.

## Contact
studer + raimann ag  
Farbweg 9  
3400 Burgdorf    
Switzerland

info@studer-raimann.ch  
www.studer-raimann.ch  
