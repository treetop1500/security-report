# security-report
A Symfony 3 bundle for automating reports with the Symfony security checker components.

*Please note: This bundle is not ready for public use yet, and is not in the Packagist repository, so the composer installation below will not work yet.*

##Installation##
Add the following to your composer.json file.

```
"require": {
    ...
    "treetop1500/security-report": "~1.0"
},
```

Add the following to your AppKernel.php

```
public function registerBundles()
    {
        $bundles = [
            ...
            new \Treetop1500\SecurityReportBundle\Treetop1500SecurityReportBundle()
        ];

        ...
    }
```

Then run `composer update`

##Configuration##

Add the following to your config:

```
#app/config.yml
treetop1500_security_report:
    key: XXXXXXXXXXXXXXXXXXXXXX
    allowable_ips: [127.0.0.1]
    show_output: true
    delivery_method: email
    email_recipients: ['me@mydomain.com']
    email_from: 'me@mydomain.com'
    advisories_only: false

```

`key` can be any alpha-numeric string that you will pass to this service as a url parameter.

`allowable_ips` is an array of IP addresses that can access this service. Can be given as a single IP or in CIDR notation (x.x.x.x/xx)

`show_output` should be set to false in production environments. Set to true when accessing the page manaually for debugging.

`delivery_method` currently the only option is 'email'

`email_recipients` is an array of email addresses to receive the report. Set in the SwiftMailer `setTo` method.

`email_from` is a single email to be used in the SwiftMailer `setFrom` method

`advisories_only` should be set to true if you want only reports containing advisories. Set to false to be notified of all reports. Default is false.


##Routing##

Import the routing:

```
#app/routing.yml
treetop1500_security_report:
    resource: "@Treetop1500SecurityReportBundle/Resources/config/routing.yml"
```

##Usage##

To run the security report, simply access the url from any configured allowable IP (replace 'XXXXX' with your configured key):

    http://mydomain.com/services/security-checker/XXXXX

###Crons###
It is recommended to set up a cron to run this checker periodically to alert you of new vulnerabilities. Make sure to add the IP addresses of the remote that the cron will be using.

If you use EasyCron, the IP addresses you need can be found here: https://www.easycron.com/ips

###To Do###
1. Complete phpUnit testing suite
2. Improve Email subject based on results
3. Explore priority email headers
4. Add a configuration setting to allow only emails to be sent on tests that reveal vulnerabilities ("OK" results are not emailed).
