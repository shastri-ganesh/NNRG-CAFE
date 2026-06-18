<?php
    //For inserting new customer to database
    include('conn_db.php');
    $pwd = $_POST["pwd"];
    $cfpwd = $_POST["cfpwd"];
    if($pwd != $cfpwd){
        ?>
        <script>
            alert('Your password is not match.\nPlease enter it again.');
            history.back();
        </script>
        <?php
        exit(1);
    }else{
        $username = $_POST["username"];
        $firstname = $_POST["firstname"];
        $lastname = $_POST["lastname"];
        $gender = $_POST["gender"];
        $email = $_POST["email"];
        $type = $_POST["type"];
        $phone_number = $_POST["phone_number"]; // NEW FIELD
        $department = $_POST["department"]; // NEW FIELD

        if($gender == "-" || $type == "-"){
            ?>
            <script>
                alert('You didn\'t select your gender or role yet.\nPlease select again!');
                history.back();
            </script>
            <?php
            exit(1);
        }

        // Check if department is required for students and faculty
        if(($type == "STD" || $type == "STF") && $department == "-"){
            ?>
            <script>
                alert('Please select your department/course!');
                history.back();
            </script>
            <?php
            exit(1);
        }

        // Validate phone number (basic validation)
        if(!preg_match("/^[0-9]{10}$/", $phone_number)){
            ?>
            <script>
                alert('Please enter a valid 10-digit phone number!');
                history.back();
            </script>
            <?php
            exit(1);
        }

        //Check for duplicating username
        $query = "SELECT c_username FROM customer WHERE c_username = '$username';";
        $result = $mysqli -> query($query);
        if($result -> num_rows >= 1){
            ?>
            <script>
                alert('Your username is already taken!');
                history.back();
            </script>
            <?php
        }
        $result -> free_result();
        
        //Check for duplicating email
        $query = "SELECT c_email FROM customer WHERE c_email = '$email';";
        $result = $mysqli -> query($query);
        if($result -> num_rows >= 1){
            ?>
            <script>
                alert('Your email is already in use!');
                history.back();
            </script>
            <?php
        }
        $result -> free_result();

        //Check for duplicating phone number
        $query = "SELECT c_phone FROM customer WHERE c_phone = '$phone_number';";
        $result = $mysqli -> query($query);
        if($result -> num_rows >= 1){
            ?>
            <script>
                alert('Your phone number is already in use!');
                history.back();
            </script>
            <?php
        }
        $result -> free_result();

        // Handle department value (set to NULL if not selected)
        $dept_value = ($department == "-") ? "NULL" : "'$department'";

        $query = "INSERT INTO customer (c_username,c_pwd,c_firstname,c_lastname,c_email,c_gender,c_type,c_phone,c_department)
        VALUES ('$username','$pwd','$firstname','$lastname','$email','$gender','$type','$phone_number',$dept_value);";

        $result = $mysqli -> query($query);

        if($result){
            header("location: cust_regist_success.php");
        }else{
            header("location: cust_regist_fail.php?err={$mysqli -> errno}");
        }
    }
?>