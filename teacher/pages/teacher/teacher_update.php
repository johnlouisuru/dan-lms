<?php
include_once "../../db-config/security.php";
header('Content-Type: application/json');

try {
    // Validate required parameters
    if (!isset($_POST['id'], $_POST['email'], $_POST['lastname'], $_POST['firstname'])) {
        throw new Exception("Missing parameters.");
    }

    $id        = (int) $_POST['id'];
    $email     = trim($_POST['email']);
    $lastname  = trim($_POST['lastname']);
    $firstname = trim($_POST['firstname']);
    // $sectionId = !empty($_POST['section_id']) ? (int) $_POST['section_id'] : null;
    $sectionId = null;

    // Update teacher record
    $stmt = $pdo->prepare("UPDATE teachers 
                           SET email = :email, lastname = :lastname, firstname = :firstname, section_id = :section_id 
                           WHERE id = :id AND is_active = 1");
    $stmt->execute([
        ':email'     => $email,
        ':lastname'  => $lastname,
        ':firstname' => $firstname,
        ':section_id'=> $sectionId,
        ':id'        => $id
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Teacher updated successfully.',
        'id'      => $id
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
