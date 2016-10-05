# security-report
A Symfony 3 bundle for automating reports with the Symfony security checker components

##Installation##
Add the following to your composer.json file, then run `composer update`
    
```
"require": {
    ...
    "treetop1500/security-report": "dev-master"
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

##Configuration##

Add the following to your config:

```
#app/config.yml
parameters:
    treetop1500_security_report
        key: XXXXXXXXXXXXXXXXXXXXXX
        allowable_ips: [127.0.0.1]
        show_output: true
        delivery_method: email
        recipients: ['me@mydomain.com']

```

`key` can be any alpha-numeric string that you will pass to this service as a url parameter.

`allowable_ips` is an array of IP addresses that can access this service.

`show_output` should be set to false in production environments. Set to true when accessing the page manaually for debugging.

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
