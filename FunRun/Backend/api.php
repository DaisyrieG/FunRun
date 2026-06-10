<?php
// 1. REST Header Configurations
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle CORS Preflight OPTIONS requests immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // 200 OK for preflight handshake
    exit();
}

// 2. Import Database Connection
require_once 'config/database.php';

// 3. Identify the REST Verb
$request_method = $_SERVER['REQUEST_METHOD'];

switch ($request_method) {
    
    /**
     * POST /backend/api.php
     * Purpose: Create a new participant resource
     * Status Codes: 201 (Created), 400 (Bad Request), 500 (Internal Server Error)
     */
    case 'POST':
        // Read the raw JSON payload from the request body
        $raw_input = file_get_contents("php://input");
        $data = json_decode($raw_input, true);

        // Validate payload data existence
        if (
            isset($data['full_name']) && 
            isset($data['email']) && 
            isset($data['phone']) && 
            isset($data['category'])
        ) {
            // Sanitize inputs to prevent XSS
            $full_name = htmlspecialchars(strip_tags(trim($data['full_name'])));
            $email     = htmlspecialchars(strip_tags(trim($data['email'])));
            $phone     = htmlspecialchars(strip_tags(trim($data['phone'])));
            $category  = htmlspecialchars(strip_tags(trim($data['category'])));

            // Basic validation check
            if (empty($full_name) || empty($email) || empty($phone) || empty($category)) {
                http_response_code(400); // Bad Request
                echo json_encode(["error" => "Validation failed. Fields cannot be empty."]);
                break;
            }

            try {
                $query = "INSERT INTO participants (full_name, email, phone, category) 
                          VALUES (:full_name, :email, :phone, :category)";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email'     => $email,
                    ':phone'     => $phone,
                    ':category'  => $category
                ]);

                // RESTful response for resource creation
                http_response_code(201); // 201 Created
                echo json_encode([
                    "status" => "success", 
                    "message" => "Participant resource created successfully."
                ]);

            } catch (PDOException $e) {
                http_response_code(500); // Internal Server Error
                echo json_encode(["error" => "Database error: Unable to create resource."]);
            }
        } else {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "Malformed payload. Required fields missing."]);
        }
        break;

    /**
     * GET /backend/api.php
     * Purpose: Read the collection of participant resources
     * Status Codes: 200 (OK), 500 (Internal Server Error)
     */
    case 'GET':
        try {
            $query = "SELECT id, full_name, email, phone, category, created_at 
                      FROM participants 
                      ORDER BY created_at DESC";
            
            $stmt = $pdo->query($query);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200); // 200 OK
            echo json_encode($participants);

        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode(["error" => "Database error: Unable to fetch collection."]);
        }
        break;

    /**
     * Catch-all for unsupported REST verbs (PUT, DELETE, etc.)
     */
    default:
        http_response_code(405); // 405 Method Not Allowed
        echo json_encode(["error" => "HTTP Method Not Allowed on this resource."]);
        break;
}
?>