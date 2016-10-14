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


class DefaultController extends Controller
{
  private $access_key,
    $allowableIps,
    $deliveryMethod,
    $showOutput,
    $email_recipients,
    $email_from,
    $host;

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

    $lockfile = $this->get('kernel')->getRootDir()."/../composer.lock";
    $remote_address = $this->get('request_stack')->getCurrentRequest()->getClientIp();

    if ($key != $this->access_key || !in_array($remote_address,$this->allowableIps)) {
      $this->sendSecurityReport(NULL, TRUE);
      throw new UnauthorizedHttpException("You are not authorized to access this.");
    }

    $kernel = new AppKernel('dev', true);
    $application = new Application($kernel);
    $application->setAutoExit(false);

    $input = new ArrayInput(array('command'=>'security:check','lockfile'=>$lockfile));

    // You can use NullOutput() if you don't need the output
    $output = new BufferedOutput();
    $application->run($input, $output);

    // return the output, don't use if you used NullOutput()
    $content = nl2br($output->fetch());

    // check if any advisories exist and set $notification_level
    if(preg_match('/\[OK\]/', $content)) {
      $advisories_exist = false;
    } else {
      $advisories_exist = true;
    }

    // check advisory setting before sending email
    if ($config['advisories_only']) {
      if($advisories_exist) {
        $this->sendSecurityReport($content);
      }
    } else {
      $this->sendSecurityReport($content);
    }

    if ($config['show_output']) {
      return new Response($content);
    }

    return new Response("Security Check Report");

  }

  private function sendSecurityReport($content, $error = false) {
    $remote_address = $this->get('request_stack')->getCurrentRequest()->getClientIp();

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
                                     'remote_address' => $remote_address,
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
                                     'remote_address' => $remote_address,
                                     'host' => $this->host
                                   )
                                 ),
                                 'text/html'
                               );
    }
    $this->get('mailer')->send($message);
  }
}
