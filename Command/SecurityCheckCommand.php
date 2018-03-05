<?php
/**
 * Created by PhpStorm.
 * User: grayloon
 * Date: 3/2/18
 * Time: 2:04 PM
 */

namespace Treetop1500\SecurityReportBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SecurityCheckCommand extends ContainerAwareCommand
{
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
   * @var boolean
   * when set to true, will not send Status OK results
   */
  private $advisories_only;
  
  /**
   * @var string
   * The path to the composer.lock file used in the security:check command
   */
  private $lockfile;
  
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('treetop:report')
      ->setDescription('Check project dependencies for security issues');
  }
  
  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $config = $this->getContainer()->getParameter('treetop1500_security_report.config');
    $this->showOutput = $config['show_output'];
    $this->deliveryMethod = $config['delivery_method'];
    $this->email_recipients = $config['email_recipients'];
    $this->email_from = $config['email_from'];
    $this->advisories_only = $config['advisories_only'];
    $this->lockfile = $this->getContainer()->get('kernel')->getRootDir()."/../composer.lock";
    
    $command = $this->getApplication()->find('security:check');
    
    $input = new ArrayInput(array('command' => 'security:check', 'lockfile' => $this->lockfile));
    
    $buffOut = new BufferedOutput();
  
    $command->run($input, $buffOut);
    
    $content = $buffOut->fetch();
    // check advisory setting before sending email
    if (($this->advisoriesExist($content) && $this->advisories_only) || !$this->advisories_only) {
      $this->sendSecurityReport($content);
    }
  }
  
  
  /**
   * @param $content string
   * @return bool
   * checks if the result of the security:check command contains an [OK] status or not.
   */
  private function advisoriesExist($content)
  {
    if (preg_match('/\[OK\]/', $content)) {
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
    $host = $this->getContainer()->get('router')->getContext()->getHost();
    
    if (!$error) {
      $message = \Swift_Message::newInstance()
        ->setSubject('New Security Check Report')
        ->setFrom($this->email_from)
        ->setTo($this->email_recipients)
        ->setBody(
          $this->getContainer()->get('templating')->render(
            'Treetop1500SecurityReportBundle:Default:report.html.twig',
            array(
              'report' => $content,
              'host' => $host,
              'remote_address' => "Application Server"
            )
          ),
          'text/html'
        );
    }
    $this->getContainer()->get('mailer')->send($message);
  }
}