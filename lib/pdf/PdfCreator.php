<?php

/* This file is part of testMaker.

testMaker is free software; you can redistribute it and/or modify
it under the terms of version 2 of the GNU General Public License as
published by the Free Software Foundation.

testMaker is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>. */


/**
 * @package Library
 */

/**
 * With this class pdf documents can be created
 *
 */
require_once(PORTAL.'BlockPage.php');
class PdfCreator extends Page
{
	/* Create a certificate
	 * 
	 * @param String First name of the user
	 * @param String Last name of the user
	 * @param String Birthday date of the user
	 * @param String Template file to use
	 * @param String Check string for this certificate
	 */
	function createMwCertificate($name, $birthday, $date, $template, $checkString, $cert_disable_barcode, $email = NULL)
	{
		require_once('tpdf.php');
		$mediaConnectId = preg_split('/_/',$template);

		$modulo = $mediaConnectId[0] % 100;
		$templateFile = ROOT."upload/media/".$modulo."/".$mediaConnectId[0]."/".$template;
		
		if(!is_file($templateFile))
			return FALSE;
		
		$pdf = new tpdf($templateFile);
		$pdf->setFontWeight("B");
		$pdf->writeTextBox(1, 98, 208, $name, 0, 'C');
		$pdf->writeTextBox(106, 109, 24, $birthday, 0);
		$pdf->setFontSize(10);
		$pdf->writeTextBox(36, 119.9, 20, $date, 0);
		$pdf->setFontWeight('n');
		$pdf->setFontSize(7);
		$pdf->writeTextBox(130, 270, 50, $checkString, 0);
		if(!$cert_disable_barcode == "1")
			{
				$pdf->Code39(20, 255,$checkString, 1, 16);
			}
		if ($email == NULL OR $email == "")
			$pdf->output();
		else {
			require_once('lib/email/Composer.php');
			$pdfString = $pdf->outputString();
			$mail = new EmailComposer();
			$mail->addAttachmentFromMemory($pdfString, "application/pdf", 'certificate.pdf');
			$mail->setSubject("testMaker Feedback ".date("d.m.Y"));
			$bodies = array(
					"html" => "UserCertMail.html",
					"text" => "UserCertMail.txt",
				);
			foreach ($bodies as $type => $templateFile) {
				$this->tpl->loadTemplateFile($templateFile);
				if($templateFile == "UserCertMail.html") $name = htmlentities($name);
				$this->tpl->setVariable("name", $name);
				$bodies[$type] = $this->tpl->get();
			}
			$mail->setHtmlMessage($bodies["html"]);
			$mail->setTextMessage($bodies["text"]);
			
			$mail->setFrom(SYSTEM_MAIL, "testMaker");
			$mail->addRecipient($email);
			$mail->sendMail();

			$pdf->output();
		}
	}
}
