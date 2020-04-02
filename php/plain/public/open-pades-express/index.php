<?php

require __DIR__ . '../../vendor/autoload.php';

use Lacuna\PkiExpress\PadesSignatureExplorer;


// Only accepts GET requests.
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
    die();
}

// Verify if the fileId correspond to an existing file on our StorageMock class.
$fileId = isset($_GET['fileId']) ? $_GET['fileId'] : null;
if (!StorageMock::exists($fileId)) {
    header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
    die();
}

// Get an instance of the PadesSignatureExplorer class, used to open/validate PDF signatures.
$sigExplorer = new PadesSignatureExplorer();

// Set PKI default options (see Util.php).
Util::setPkiDefaults($sigExplorer);

// Set the PDF file to be inspected.
$sigExplorer->setSignatureFile(StorageMock::getDataPath($fileId));

// Specify that we want to validate the signatures in the file, not only inspect them.
$sigExplorer->validate = true;

// Call the open() method, which returns the signature file's information.
$signature = $sigExplorer->open();

?>
<!DOCTYPE html>
<html>
<head>
    <?php include '../shared/head.php' ?>
</head>
<body>

<?php include '../shared/menu.php' ?>

<div class="container content">
    <div id="messagesPanel"></div>

    <h2 class="ls-title">Open/validate existing PAdES signature with PKI Express</h2>
    <h3 class="ls-subtitle">The given file contains <?= count($signature->signers) ?> signatures:</h3>

    <div class="ls-content">
        <div id="accordion">
            <?php for ($i = 0; $i < count($signature->signers); $i++) {

                $signer = $signature->signers[$i];
                $collapseId = "signer_" . $i . "_collapse";
                $headingId = "signer_" . $i . "_heading";

                ?>
                <div class="card">
                    <div class="card-header open-header" id="<?= $headingId ?>">
                        <a class="collapsed" role="button" data-toggle="collapse" href="#<?= $collapseId ?>" aria-expanded="true" aria-controls="<?= $collapseId ?>"><?= $signer->certificate->subject->commonName ?></a>
                        <?php if ($signer->validationResults != null) { ?>
                            <span>&nbsp;</span>
                            <?php if ($signer->validationResults->isValid()) { ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php } else { ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <div id="<?= $collapseId ?>" class="collapse" role="tabpanel" aria-labelledby="<?= $headingId ?>" data-parent="#accordion">
                        <div class="card-body">
                            <?php if ($signer->signingTime != null) { ?>
                                <p><strong>Signing time</strong>: <?= date("d/m/Y H:i", strtotime($signer->signingTime)) ?></p>
                            <?php } ?>

                            <p><strong>Message digest</strong>: <?= $signer->messageDigest->algorithm->name . " " . strtoupper($signer->messageDigest->hexValue) ?></p>
                            <?php if ($signer->signaturePolicy != null) { ?>
                                <p>Signature policy: <?= $signer->signaturePolicy->oid ?></p>
                            <?php } ?>

                            <p><strong>Signer information</strong>:</p>
                            <ul>
                                <li><strong>Subject</strong>: <?= $signer->certificate->subjectName->commonName ?></li>
                                <li><strong>Email</strong>: <?= $signer->certificate->emailAddress ?></li>
                                <li>
                                    <strong>ICP-Brasil fields</strong>
                                    <ul>
                                        <li><strong>Tipo de certificado</strong>: <?= $signer->certificate->pkiBrazil->certificateType ?></li>
                                        <li><strong>CPF</strong>: <?= $signer->certificate->pkiBrazil->cpfFormatted ?></li>
                                        <li><strong>Responsavel</strong>: <?= $signer->certificate->pkiBrazil->responsavel ?></li>
                                        <li><strong>Empresa</strong>: <?= $signer->certificate->pkiBrazil->companyName ?></li>
                                        <li><strong>CNPJ</strong>: <?= $signer->certificate->pkiBrazil->cnpjFormatted ?></li>
                                        <li><strong>RG</strong>: <?= $signer->certificate->pkiBrazil->rgNumero . " " . $signer->certificate->pkiBrazil->rgEmissor . " " . $signer->certificate->pkiBrazil->rgEmissorUF ?></li>
                                        <li><strong>OAB</strong>: <?= $signer->certificate->pkiBrazil->oabNumero . " " . $signer->certificate->pkiBrazil->oabUF ?></li>
                                    </ul>
                                </li>
                            </ul>
                            <?php if ($signer->validationResults != null) { ?>
                                <label for="validations">Validation results:
                                    <textarea id=" validations" style="width: 100%;" rows="20"><?= $signer->validationResults ?></textarea>
                                </label>
                            <?php } ?>
                        </div>
                    </div>
                </div>

            <?php } ?>
        </div>
    </div>
</div>

</html>
