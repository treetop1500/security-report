<?php

namespace Treetop1500\SecurityReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpFoundation\Request;
use AppKernel;

/**
 * Class DefaultController
 * @package Treetop1500\SecurityReportBundle\Controller
 * @author http://github.com/treetop1500
 */
class DefaultController extends Controller
{
    /**
     * @var string
     * stores the value of the access key in the configuration
     */
    private $access_key;

    /**
     * @var array
     * array of the allowable ip addresses in the configuration
     */
    private $allowableIps;

    /**
     * @var string
     * how the report is delivered (email, eventually slack)
     */
    private $deliveryMethod;

    /**
     * @var boolean
     * whether to show the output in the view. Useful during development, but should be set to false in production
     */
    private $showOutput;

    /**
     * @var array
     * array of recipients to receive reports
     */
    private $email_recipients;

    /**
     * @var string
     * email sender
     */
    private $email_from;

    /**
     * @var string
     * holds the current host domain which is sent with the report
     */
    private $host;

    /**
     * @var boolean
     * when set to true, will not send Status OK results
     */
    private $advisories_only;

    /**
     * @var string
     * The IP address of the remote user
     */
    private $remote_address;

    /**
     * @var string
     * The path to the composer.lock file used in the security:check command
     */
    private $lockfile;


    /**
     * @var boolean
     * Is this a request from CloudFlare?
     */
    private $isCloudFlare;

    /**
     * @param Request $request
     * @param $key
     * @return Response
     * @throws \Exception
     */
    public function indexAction(Request $request, $key)
    {
        $config = $this->getParameter('treetop1500_security_report.config');
        $this->access_key = $config['key'];
        $this->allowableIps = $config['allowable_ips'];
        $this->showOutput = $config['show_output'];
        $this->deliveryMethod = $config['delivery_method'];
        $this->email_recipients = $config['email_recipients'];
        $this->email_from = $config['email_from'];
        $this->host = $request->getHost();
        $this->advisories_only = $config['advisories_only'];
        $this->remote_address = $this->get('request_stack')->getCurrentRequest()->getClientIp();
        $this->lockfile = $this->get('kernel')->getRootDir()."/../composer.lock";
        $this->isCloudFlare = ($request->query->get('server') == "cloudflare-nginx" ? true : false);

        if ($key != $this->access_key || (!in_array($this->remote_address,$this->allowableIps) || !$this->isCloudFlare)) {
          $this->sendSecurityReport(NULL, TRUE);
          throw new UnauthorizedHttpException("You are not authorized to access this.");
        }

        $kernel = new AppKernel('dev', true);
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array('command'=>'security:check','lockfile'=>$this->lockfile));

        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput();
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = nl2br($output->fetch());

        // check advisory setting before sending email
        if (($this->advisoriesExist($content) && $this->advisories_only) || !$this->advisories_only) {
            $this->sendSecurityReport($content);
        }

        if ($config['show_output']) {
          return new Response($content);
        }

        return new Response("Security Check Report");
      }

    /**
     * @param $content string
     * @return bool
     * checks if the result of the security:check command contains an [OK] status or not.
     */
    private function advisoriesExist($content) {
        if(preg_match('/\[OK\]/', $content)) {
          return false;
        }

        return true;
    }

    /**
    * @param $content
    * @param bool $error
    * Sends the notification with Swiftmailer. If error, then a notification will be sent.
    * If not the report results are sent.
    */
    private function sendSecurityReport($content, $error = false)
    {
        if(!$error) {
          $message = \Swift_Message::newInstance()
           ->setSubject('New Security Check Report')
           ->setFrom($this->email_from)
           ->setTo($this->email_recipients)
           ->setBody(
             $this->renderView(
               'Treetop1500SecurityReportBundle:Default:report.html.twig',
               array(
                 'report' => $content,
                 'remote_address' => $this->remote_address,
                 'host' => $this->host
               )
             ),
             'text/html'
           );
        } else {
          $message = \Swift_Message::newInstance()
             ->setSubject('Unauthorized Security Check')
             ->setTo($this->email_recipients)
             ->setFrom($this->email_from)
             ->setBody(
               $this->renderView(
                 'Treetop1500SecurityReportBundle:Default:notification.html.twig',
                 array(
                   'remote_address' => $this->remote_address,
                   'host' => $this->host
                 )
               ),
               'text/html'
             );
        }
        $this->get('mailer')->send($message);
    }
}
