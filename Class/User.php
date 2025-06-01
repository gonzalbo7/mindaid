<?php
include_once 'Class/Database.php';
Class User extends Database {
    public function admin_login($un, $pass) {
        // Use prepared statement to prevent SQL injection
        $stmt = $this->conn->prepare("SELECT * FROM user_admin WHERE username = ?");
        $stmt->bind_param("s", $un);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($row = $result->fetch_assoc()) {
            // Now verify the hashed password
            if (password_verify($pass, $row['password'])) {
                return $row;
            }
        }
    
        return null;
    }

    
    public function counselor_login($un, $pass){
        $sql = "SELECT * FROM user_counselor WHERE username='$un'";
        $result = $this->conn->query($sql);

        if ($row = $result->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                return $row;
            }
        }
        
        return null;
    }
    
    public function student_login($un, $pass){
        $sql = "SELECT * FROM user_student WHERE username='$un'";
        $result = $this->conn->query($sql);
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($pass, $row['password'])) {
                return $row;
            }
        }
        
        return null;
    }    

    //COUNSELOR ACCOUNT
    public function create_counselor_acc($counselorid, $firstName, $middleName, $lastName, $contact, $email, $username, $password) {
        $sql = "INSERT INTO user_counselor VALUES (NULL, '$counselorid', '', '$firstName', '$middleName', '$lastName', '$contact', '$email', '$username', '$password', 'Guidance Counselor', CURRENT_TIMESTAMP,'','')";
        if ($this->conn->query($sql)){
            return 'Guidance Counselor Account has been Successfully Created';
        } else{
            return $this->conn->error;
        }
    }

    public function update_counselor_acc($counselorid, $firstName, $middleName, $lastName, $contact, $email, $username, $password) {
        $sql="UPDATE user_counselor SET first_name = '$firstName', middle_name = '$middleName', last_name = '$lastName', contact_number = '$contact', email = '$email', username = '$username', password = '$password' WHERE counselor_id='$counselorid'";
        if ($this->conn->query($sql)) {
            return 'Guidance Counselor Account Information Successfully Updated';
        } else {
            return $this->conn->error;
        }
    }

    public function blocked_counselor_acc($counselor_id) {
        $sql = "SELECT * FROM user_counselor WHERE counselor_id = '$counselor_id'";
        $result = $this->conn->query($sql);
    
        if ($result->num_rows > 0) {
            $counselor = $result->fetch_assoc();
    
            // Corrected variable usage
            $counselorid = $counselor['counselor_id'];
            $firstName = $counselor['first_name'];
            $middleName = $counselor['middle_name'];
            $lastName = $counselor['last_name'];
            $contact = $counselor['contact_number'];
            $email = $counselor['email'];
            $course = '';
            $year_level = '';
            $username = '';
            $password = '';
            $role = 'Counselor'; // role is always "Counselor"
    
            $sql = "INSERT INTO deleted_acc VALUES (NULL, '$counselorid', '$firstName', '$middleName', '$lastName', '$contact', '$email', '', '','','', '$role', CURRENT_TIMESTAMP)";
    
            if ($this->conn->query($sql)) {
                $sql = "DELETE FROM user_counselor WHERE counselor_id = '$counselor_id'";
                if ($this->conn->query($sql)) {
                    return 'The Guidance Counselor Account access has been successfully restricted';
                } else {
                    return $this->conn->error;
                }
            } else {
                return $this->conn->error;
            }
        } else {
            return 'Student not found.';
        }
    }

    public function display_counselor_acc(){
        $sql="SELECT counselor_id, first_name, middle_name, last_name, contact_number, email FROM user_counselor";
        $data = $this->conn->query($sql);
        return $data;
    }

    public function display_counselor_acc_by_id($counselor_id) {
        $stmt = $this->conn->prepare("SELECT * FROM user_counselor WHERE counselor_id = ?");
        $stmt->bind_param("s", $counselor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function update_counselor_avatar($counselor_id, $avatar) {
        $sql="UPDATE user_counselor SET avatar='$avatar' WHERE counselor_id='$counselor_id'";
        if ($this->conn->query($sql)) {
            return 'Counselor Profile Picture Successfully Uploaded!';
        } else {
            return $this->conn->error;
        }
    }
    

    // STUDENT APPLICATION ACCOUNT
    public function create_student_acc($studentid, $firstName, $middleName, $lastName, $contact, $email, $course, $year_level, $username, $password) {
        $sql = "INSERT INTO user_student VALUES (NULL, '', '$studentid', '$firstName', '$middleName', '$lastName', '$contact', '$email', '$course', '$year_level', '$username', '$password', 'Student', CURRENT_TIMESTAMP, 'Enabled','','', 0)";
        if ($this->conn->query($sql)){
            return 'Student Account has been Successfully Created';
        } else{
            return $this->conn->error;
        }
    }
    
    public function updateTermsAcceptance($studentid) {
        $sql = "UPDATE user_student SET terms_accepted = 1 WHERE student_id = '$studentid'";
        if ($this->conn->query($sql)) {
            return 'I confirm that I have read and agree to the terms and conditions.';
        } else {
            return $this->conn->error;
        }
    }

    public function hasAcceptedTerms($studentid) {
        $sql = "SELECT terms_accepted FROM user_student WHERE student_id = '$studentid'";
        $data = $this->conn->query($sql);
        if ($data && $row = $data->fetch_assoc()) {
            return (bool)$row['terms_accepted']; // Return true if accepted, false otherwise
        }
        return false; // Default to false if no data found
    }

    public function update_student_acc($studentid, $firstName, $middleName, $lastName, $contact, $email, $course, $year_level, $username, $password) {
        $sql="UPDATE user_student SET first_name = '$firstName', middle_name = '$middleName', last_name = '$lastName', contact_number = '$contact', email = '$email', course = '$course', year_level = '$year_level', username = '$username', password = '$password' WHERE student_id='$studentid'";
        if ($this->conn->query($sql)) {
            return 'Student Account Information Successfully Updated';
        } else {
            return $this->conn->error;
        }
    }

    public function update_student_avatar($student_id, $avatar) {
        $sql="UPDATE user_student SET avatar='$avatar' WHERE student_id='$student_id'";
        if ($this->conn->query($sql)) {
            return 'Student Profile Picture Successfully Uploaded!';
        } else {
            return $this->conn->error;
        }
    }

    public function blocked_student_acc($user_id) {
        // Get student data from user_student
        $sql = "SELECT * FROM user_student WHERE student_id = '$user_id'";
        $result = $this->conn->query($sql);
    
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
    
            // Prepare data to insert into deleted_acc
            $sql = "INSERT INTO deleted_acc VALUES (NULL, 
                '$user_id', '{$student['first_name']}', '{$student['middle_name']}',
                '{$student['last_name']}', '{$student['contact_number']}', '{$student['email']}',
                '{$student['course']}', '{$student['year_level']}', '{$student['username']}',
                '{$student['password']}', 'Student', CURRENT_TIMESTAMP
            )
            ";    
            if ($this->conn->query($sql)) {
                // Archive test results
                $testResults = $this->conn->query("SELECT * FROM test_results WHERE student_id = '$user_id'");
    
                if ($testResults->num_rows > 0) {
                    while ($row = $testResults->fetch_assoc()) {
                        $sql = "INSERT INTO archived_test_results (
                                    result_id, student_id, first_name, middle_name, last_name, email,
                                    course, year_level, depression_score, depression_class,
                                    anxiety_score, anxiety_class, stress_score, stress_class,
                                    date_taken, test_status, remark_status, remarks, remarks_sent_date, remark_by
                                ) VALUES (
                                    '{$row['result_id']}', '{$row['student_id']}', '{$row['first_name']}',
                                    '{$row['middle_name']}', '{$row['last_name']}', '{$row['email']}',
                                    '{$row['course']}', '{$row['year_level']}', '{$row['depression_score']}',
                                    '{$row['depression_class']}', '{$row['anxiety_score']}',
                                    '{$row['anxiety_class']}', '{$row['stress_score']}', '{$row['stress_class']}',
                                    '{$row['date_taken']}', '{$row['test_status']}', '{$row['remark_status']}',
                                    '{$row['remarks']}', '{$row['remarks_sent_date']}', '{$row['remark_by']}'
                                )";
                        $this->conn->query($sql);
                    }
    
                    // Delete test results from test_results
                    $this->conn->query("DELETE FROM test_results WHERE student_id = '$user_id'");
                }
    
                // Delete student from user_student
                $this->conn->query("DELETE FROM user_student WHERE student_id = '$user_id'");
    
                return 'The Student Account access has been successfully restricted';
            } else {
                return $this->conn->error;
            }
        } else {
            return 'Student not found in user_student.';
        }
    }    
    
    
    public function display_student_acc(){
        $sql="SELECT student_id, first_name, middle_name, last_name, contact_number, email, course, year_level FROM user_student ORDER BY id DESC";
        $data = $this->conn->query($sql);
        return $data;
    }

    public function display_student_acc_by_id($student_id) {
        $stmt = $this->conn->prepare("SELECT * FROM user_student WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function recover_student_account($user_id) {
        // Get student data from deleted_acc
        $sql = "SELECT * FROM deleted_acc WHERE user_id = '$user_id'";
        $result = $this->conn->query($sql);
    
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
    
            // Manually define student_id and contact_number from deleted_acc fields
            $student_id = $user_id; // Assuming student_id in user_student == user_id in deleted_acc
            $contact_number = $student['contact']; // renamed from 'contact'
    
            // Insert back into user_student
            $sql = "INSERT INTO user_student (
                        student_id, first_name, middle_name, last_name, contact_number,
                        email, course, year_level, username, password
                    ) VALUES (
                        '$student_id', '{$student['first_name']}', '{$student['middle_name']}',
                        '{$student['last_name']}', '$contact_number', '{$student['email']}',
                        '{$student['course']}', '{$student['year_level']}', '{$student['username']}',
                        '{$student['password']}'
                    )";
    
            if ($this->conn->query($sql)) {
                // Recover test results
                $archivedResults = $this->conn->query("SELECT * FROM archived_test_results WHERE student_id = '$student_id'");
                if ($archivedResults->num_rows > 0) {
                    while ($row = $archivedResults->fetch_assoc()) {
                        $sql = "INSERT INTO test_results (
                                    result_id, student_id, first_name, middle_name, last_name, email,
                                    course, year_level, depression_score, depression_class,
                                    anxiety_score, anxiety_class, stress_score, stress_class,
                                    date_taken, test_status, remark_status, remarks, remarks_sent_date, remark_by
                                ) VALUES (
                                    '{$row['result_id']}', '{$row['student_id']}', '{$row['first_name']}',
                                    '{$row['middle_name']}', '{$row['last_name']}', '{$row['email']}',
                                    '{$row['course']}', '{$row['year_level']}', '{$row['depression_score']}',
                                    '{$row['depression_class']}', '{$row['anxiety_score']}',
                                    '{$row['anxiety_class']}', '{$row['stress_score']}', '{$row['stress_class']}',
                                    '{$row['date_taken']}', '{$row['test_status']}', '{$row['remark_status']}',
                                    '{$row['remarks']}', '{$row['remarks_sent_date']}', '{$row['remark_by']}'
                                )";
                        $this->conn->query($sql);
                    }
    
                    // Delete test results from archive
                    $this->conn->query("DELETE FROM archived_test_results WHERE student_id = '$student_id'");
                }
    
                // Delete student from deleted_acc
                $this->conn->query("DELETE FROM deleted_acc WHERE user_id = '$user_id'");
    
                return 'Student Account and Test Results Successfully Recovered!';
            } else {
                return $this->conn->error;
            }
        } else {
            return 'Student record not found in deleted accounts.';
        }
    }    

    public function get_deleted_student_accounts() {
        $sql = "SELECT * FROM deleted_acc WHERE role = 'student'";
        $data = $this->conn->query($sql);
        return $data;
    }    
    
    // CRUDE QUESTIONNAIRE 
    public function save_questionnaire($questionid, $question_text, $category){
        // Escape special characters to prevent syntax errors
        $questionid = $this->conn->real_escape_string($questionid);
        $question_text = $this->conn->real_escape_string($question_text);
        $category = $this->conn->real_escape_string($category);

        $sql = "INSERT INTO dass42_questionnaire VALUES(NULL, '$questionid', '$question_text', '$category', CURRENT_TIMESTAMP)";
        
        if($this->conn->query($sql)){
            return 'Question Successfully Saved!';
        } else{
            return $this->conn->error;
        }
    }
    
    public function count_questions() {
        $sql = "SELECT COUNT(*) AS total FROM dass42_questionnaire";
        $data = $this->conn->query($sql);
        return $data;
    }

    public function displayallquestions(){
        $sql="SELECT * FROM dass42_questionnaire ORDER BY id DESC";
        $data = $this->conn->query($sql);
        return $data;
    }

    public function updatequestions($questionid, $question_text, $category){
        $sql="UPDATE dass42_questionnaire SET questions='$question_text', category='$category' WHERE question_id='$questionid'";
        if ($this->conn->query($sql)) {
            return 'Question Details Successfully Updated';
        } else {
            return $this->conn->error;
        }
    }

    public function deletequestion($questionid){
        $sql="DELETE FROM dass42_questionnaire WHERE question_id='$questionid'";
        if ($this->conn->query($sql)) {
            return 'Question Details Successfully Deleted!';
        } else {
            return $this->conn->error;
        }
    }

    // TEST RESULT SAVING
    public function save_test_result($resultid, $student_id, $first_name, $middle_name, $last_name, $email, $course, $year_level, $depression_score, $depression_class, $anxiety_score, $anxiety_class, $stress_score, $stress_class, $status, $remarks, $remarks_sent_date) {
    $sql = "INSERT INTO test_results VALUES (NULL, '$resultid', '$student_id', '$first_name','$middle_name','$last_name', '$email', '$course', '$year_level', '$depression_score', '$depression_class', '$anxiety_score', '$anxiety_class', '$stress_score', '$stress_class', NOW(), '$status','$remarks','$remarks_sent_date')";
        if ($this->conn->query($sql)) {
            return 'Test result has been successfully saved';
        } else {
            return $this->conn->error;
        }
    }

    public function student_save_test_result($resultid, $student_id, $first_name, $middle_name, $last_name, $email, $course, $year_level, $depression_score, $depression_class, $anxiety_score, $anxiety_class, $stress_score, $stress_class, $status) {
        $date_taken = date('Y-m-d H:i:s'); // Get current date and time
        $sql = "INSERT INTO test_results VALUES (NULL, '$resultid', '$student_id', '$first_name', '$middle_name', '$last_name', '$email', '$course', '$year_level', '$depression_score', '$depression_class', '$anxiety_score', '$anxiety_class', '$stress_score', '$stress_class', '$date_taken', '$status', 'Pending', '', '', '')";
        
        if ($this->conn->query($sql)) {
            return 'Test result has been Successfully Recorded';
        } else {
            return $this->conn->error;
        }
    }

    public function updateRemark($result_id, $remark, $date_sent, $remark_by) {
        $sql = "UPDATE test_results SET remarks = '$remark', remarks_sent_date = '$date_sent', remark_by = '$remark_by' WHERE result_id = '$result_id'";
            if ($this->conn->query($sql)) {
                return 'Remarks have been successfully recorded and sent to the student via email';
            } else {
                return $this->conn->error;
            }
    }
        
    public function displayAllTestResult() {
        $sql = "SELECT *,
                    LEAST(
                        CASE depression_class
                            WHEN 'Extremely Severe' THEN 1
                            WHEN 'Severe' THEN 2
                            WHEN 'Moderate' THEN 3
                            WHEN 'Mild' THEN 4
                            WHEN 'Normal' THEN 5
                            ELSE 6
                        END,
                        CASE anxiety_class
                            WHEN 'Extremely Severe' THEN 1
                            WHEN 'Severe' THEN 2
                            WHEN 'Moderate' THEN 3
                            WHEN 'Mild' THEN 4
                            WHEN 'Normal' THEN 5
                            ELSE 6
                        END,
                        CASE stress_class
                            WHEN 'Extremely Severe' THEN 1
                            WHEN 'Severe' THEN 2
                            WHEN 'Moderate' THEN 3
                            WHEN 'Mild' THEN 4
                            WHEN 'Normal' THEN 5
                            ELSE 6
                        END
                    ) AS severity_rank
                FROM test_results
                ORDER BY severity_rank ASC,
                    depression_score DESC,
                    anxiety_score DESC,
                    stress_score DESC";
    
        $result = $this->conn->query($sql);
    
        if(!$result) {
            die("Query failed: " . $this->conn->error);
        }
    
        return $result;
    }
    

    //Disabling  Test
    public function getStudentTestStatus($student_id) {
        $sql = "SELECT test_status FROM user_student WHERE student_id = '$student_id' LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    
    public function updateTestStatus($student_id, $status) {
        // Start transaction
        $this->conn->begin_transaction();
        try {
            // First update the test_results table
            $sql1 = "UPDATE test_results SET test_status = '$status' WHERE student_id = '$student_id'";
            if (!$this->conn->query($sql1)) {
                throw new Exception("Failed to update test results status.");
            }
    
            // Then update the user_student table (you can add additional fields to update as necessary)
            $sql2 = "UPDATE user_student SET test_status = '$status' WHERE student_id = '$student_id'";
            if (!$this->conn->query($sql2)) {
                throw new Exception("Failed to update student test status.");
            }
    
            // Commit transaction
            $this->conn->commit();
            return 'The student\'s test has been successfully recorded, and their test status has been updated to disabled. Please note: The counselor\'s remarks will be sent to the provided email. If the email is not received, kindly check your Spam or Junk folder in Gmail.';
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $this->conn->rollback();
            return $e->getMessage();
        }
    }
    
    

    public function check_if_test_taken($student_id) {
        $stmt = $this->conn->prepare("SELECT * FROM test_results WHERE student_id = ? AND test_status = 'Disable'");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            return true; // Test already taken
        } else {
            return false; // Test not yet taken
        }
    }
    
    public function is_test_completed($student_id) {
        $sql = "SELECT * FROM test_results WHERE student_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        return $result->num_rows > 0;
    }

    public function get_test_status($student_id) {
        $sql = "SELECT test_status FROM test_results WHERE student_id = '$student_id' ORDER BY date_taken DESC LIMIT 1";
        $data = $this->conn->query($sql);
        if ($data && $data->num_rows > 0) {
            return $data;
        } else {
            return false;
        }
    }
    
    //Enabling Test  
    public function enableTest($student_id) {
        $sql1 = "UPDATE test_results 
                SET test_status = 'Enabled'
                WHERE student_id = '$student_id' 
                AND date_taken = (SELECT MAX(date_taken) FROM test_results WHERE student_id = '$student_id')";
        
        if ($this->conn->query($sql1)) {
            // Update user_student table
            $sql2 = "UPDATE user_student 
                    SET test_status = 'Enabled' 
                    WHERE student_id = '$student_id'";
            
            if ($this->conn->query($sql2)) {
                return 'Test Status Successfully Updated';
            } else {
                return 'Error updating user_student table: ' . $this->conn->error;
            }
        } else {
            return 'Error updating test_results table: ' . $this->conn->error;
        }
    }    
    
    // Function to get the latest test result for each student
    public function getLatestTestResultIds() {
        $latestTestResultIds = [];
    
        $sql = "SELECT t1.student_id, t1.result_id
                FROM test_results t1
                INNER JOIN (
                    SELECT student_id, MAX(date_taken) AS latest_date
                    FROM test_results
                    GROUP BY student_id
                ) t2 ON t1.student_id = t2.student_id AND t1.date_taken = t2.latest_date";
    
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->get_result();
    
        if ($data->num_rows > 0) {
            while ($row = $data->fetch_assoc()) {
                $latestTestResultIds[$row['student_id']] = $row['result_id']; // Store by student_id
            }
        }
    
        $stmt->close();
    
        return $latestTestResultIds;
    }
    
    //Badge
    public function getPendingRemarkCount() {
        $sql = "SELECT COUNT(*) AS pending_count FROM test_results WHERE remark_status = 'Pending'";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (int) $row['pending_count']; // Return the actual count as an integer
        }
        return 0; // Return 0 if query fails
    }

    public function getCompletedRemarkCount() {
        $sql = "SELECT COUNT(*) AS completed_count FROM test_results WHERE remark_status = 'Completed'";
        $result = $this->conn->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            return (int) $row['completed_count'];
        }
        return 0; // Return 0 if query fails
    }
    
    public function getStudentByResultId($result_id) {
        $sql = "SELECT email, first_name, middle_name, last_name FROM test_results WHERE result_id = '$result_id'";
        $result = $this->conn->query($sql);
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc(); // this returns an associative array
            }    
             return null; // in case no result is found
    }

    public function updateRemarkStatus($result_id, $status){
        $sql="UPDATE test_results SET remark_status = '$status' WHERE result_id = '$result_id'";
        if ($this->conn->query($sql)) {
            return 'Remarks Status Successfully Updated';
        } else {
            return $this->conn->error;
        }
    }
    
    public function getTestResultById($result_id) {
        $sql="SELECT * FROM test_results WHERE result_id = '$result_id'";
        $result = $this->conn->query($sql);
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc(); // this returns an associative array
            }    
             return null; // in case no result is found
    }

    public function getLastTestDate($student_id) {
        $sql = "SELECT date_taken FROM test_results WHERE student_id = '$student_id' ORDER BY date_taken DESC LIMIT 1";
        $data = $this->conn->query($sql);
        
        if ($row = $data->fetch_assoc()) {
            return $row['date_taken']; // Return raw date without modification
        }
        return null;
    }

    //DISPLAYING OF TEST RESULTS IN STUDENT PAGE
    public function getResultsByStudentId($studentId) {
        $stmt = $this->conn->prepare("SELECT * FROM test_results WHERE student_id = ? ORDER BY id DESC");
        $stmt->bind_param("s", $studentId); // assuming student_id is stored as string like "STUDENT-455775"
        $stmt->execute();
        $result = $stmt->get_result();
    
        $results = [];
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
        return $results;
    }

    //Admin Reports
    public function getFilteredResults() {
        $sql = "SELECT * FROM test_results WHERE 1=1";
        
        if (isset($this->filters) && is_array($this->filters)) {
            foreach ($this->filters as $key => $value) {
                if (!empty($value)) {
                    if ($key === 'start_date') {
                        $sql .= " AND date_taken >= '" . $this->conn->real_escape_string($value) . "'";
                    } elseif ($key === 'end_date') {
                        $sql .= " AND date_taken <= '" . $this->conn->real_escape_string($value) . "'";
                    } else {
                        $sql .= " AND $key = '" . $this->conn->real_escape_string($value) . "'";
                    }
                }
            }
        }
    
        // Add ORDER BY clause
        $sql .= " ORDER BY id DESC";
    
        return $this->conn->query($sql);
    }    

    //STUDENT CHANGE PASSWORD 
    public function update_student_password($student_id, $hashed_password) {
        $sql = "UPDATE user_student SET password = ? WHERE student_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $student_id);
        return $stmt->execute(); // return true/false
    }   

    //COUNSELOR CHANGE PASSWORD
    public function update_counselor_password($counselor_id, $hashed_password) {
        $sql = "UPDATE user_counselor SET password = ? WHERE counselor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $counselor_id);
        return $stmt->execute(); // return true/false
    }   
    
    
    
    //COUNSELOR AND STUDENT FORGOT AND RESET PASSWORD
    public function findUserByEmail($email) {
        // First, check for counselor
        $stmt = $this->conn->prepare("SELECT * FROM user_counselor WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $counselor = $stmt->get_result()->fetch_assoc();

        if ($counselor) {
            return ['user_type' => 'counselor', 'user_data' => $counselor];
        }
        
        // If not found in counselors, check for student
        $stmt = $this->conn->prepare("SELECT * FROM user_student WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();

        if ($student) {
            return ['user_type' => 'student', 'user_data' => $student];
        }

        return null; // Email not found
    }

    public function storeResetToken($email, $token, $user_type) {
        date_default_timezone_set('Asia/Manila');
        $expiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        if ($user_type == 'counselor') {
            $sql = "UPDATE user_counselor SET reset_token = ?, token_expiry = ? WHERE email = ?";
        } else {
            $sql = "UPDATE user_student SET reset_token = ?, token_expiry = ? WHERE email = ?";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $token, $expiry, $email);
        return $stmt->execute();
    }
    

    public function findUserByToken($token) {
        $stmt = $this->conn->prepare("SELECT * FROM user_counselor WHERE reset_token = ? AND token_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            return ['user_type' => 'counselor', 'user_data' => $user];
        }

        // Check for student
        $stmt = $this->conn->prepare("SELECT * FROM user_student WHERE reset_token = ? AND token_expiry > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            return ['user_type' => 'student', 'user_data' => $user];
        }

        return null; // Token not found or expired
    }

    public function updateUserPassword($userId, $newHashedPassword, $user_type) {
        if ($user_type == 'counselor') {
            $stmt = $this->conn->prepare("UPDATE user_counselor SET password = ?, reset_token = NULL, token_expiry = NULL WHERE counselor_id = ?");
        } else {
            $stmt = $this->conn->prepare("UPDATE user_student SET password = ?, reset_token = NULL, token_expiry = NULL WHERE student_id = ?");
        }

        $stmt->bind_param("si", $newHashedPassword, $userId);
        return $stmt->execute();
    }

    //FILTERED DASHBOARD
    public function getDashboardData($filters) {
    $data = [
        'totalStudents' => 0,
        'totalCounselors' => 0,
        'testsTaken' => 0,
        'chartData' => [],
        'filters' => $filters
    ];

    $yearLevel = $filters['year_level'] ?? 'All';

    // Total students
    $studentQuery = "SELECT COUNT(*) AS total_students FROM user_student";
    $result = mysqli_query($this->conn, $studentQuery);
    if ($result) {
        $data['totalStudents'] = mysqli_fetch_assoc($result)['total_students'];
    }

    // Total counselors
    $result = mysqli_query($this->conn, "SELECT COUNT(*) AS total_counselors FROM user_counselor");
    if ($result) {
        $data['totalCounselors'] = mysqli_fetch_assoc($result)['total_counselors'];
    }

    // Total tests
    $result = mysqli_query($this->conn, "SELECT COUNT(*) AS tests_taken FROM test_results");
    if ($result) {
        $data['testsTaken'] = mysqli_fetch_assoc($result)['tests_taken'];
    }

    // Total questionnaire
    $result = mysqli_query($this->conn, "SELECT COUNT(*) AS total_questionnaire FROM dass42_questionnaire");
    if ($result) {
        $data['questionnaire'] = mysqli_fetch_assoc($result)['total_questionnaire'];
    }

    // WHERE clause for filtering
    $whereClause = "";
    if ($yearLevel !== 'All') {
        $yearLevelEscaped = mysqli_real_escape_string($this->conn, $yearLevel);
        $whereClause = "WHERE year_level = '$yearLevelEscaped'";
    }

    // Classification query with year_level included
    $classificationQuery = "
        SELECT course, year_level, 
            depression_class AS classification, COUNT(*) as count, 'Depression' AS type
        FROM test_results
        $whereClause
        GROUP BY course, year_level, depression_class

        UNION ALL

        SELECT course, year_level, 
            anxiety_class AS classification, COUNT(*) as count, 'Anxiety' AS type
        FROM test_results
        $whereClause
        GROUP BY course, year_level, anxiety_class

        UNION ALL

        SELECT course, year_level, 
            stress_class AS classification, COUNT(*) as count, 'Stress' AS type
        FROM test_results
        $whereClause
        GROUP BY course, year_level, stress_class
    ";

    $result = mysqli_query($this->conn, $classificationQuery);

    $courseData = []; // [course_year][type][classification] => count
    $totalPerCourse = []; // [course_year][type] => total count

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $course = $row['course'];
            $year_level = $row['year_level'];
            $classification = $row['classification'];
            $type = $row['type'];
            $count = (int)$row['count'];

            // Abbreviate course names for display
            $abbreviatedCourse = str_replace([
                'Bachelor of Science in Computer Science',
                'Bachelor of Science in Criminology',
                'Bachelor of Science in Management Accounting',
                'Bachelor of Public Administration'
            ], ['BSCS', 'BSCrim', 'BSMA', 'BPA'], $course);
            
            $course_year = $abbreviatedCourse . ' - ' . $year_level;


            if (!isset($courseData[$course_year])) {
                $courseData[$course_year] = [];
            }

            if (!isset($courseData[$course_year][$type])) {
                $courseData[$course_year][$type] = [
                    'Normal' => 0,
                    'Mild' => 0,
                    'Moderate' => 0,
                    'Severe' => 0,
                    'Extremely Severe' => 0
                ];
                $totalPerCourse[$course_year][$type] = 0;
            }

            $courseData[$course_year][$type][$classification] = $count;
            $totalPerCourse[$course_year][$type] += $count;
        }

        foreach ($courseData as $course_year => $types) {
            $entry = ['label' => $course_year];

            foreach ($types as $type => $classifications) {
                foreach (['Normal', 'Mild', 'Moderate', 'Severe', 'Extremely Severe'] as $classification) {
                    $normalizedClassification = str_replace(' ', '_', $classification);
                    $key = "{$type}_{$normalizedClassification}";
                    $entry[$key] = isset($classifications[$classification]) ? $classifications[$classification] : 0;
                }
            }

            $data['chartData'][] = $entry;
        }
    }

    return $data;
}





}
?>