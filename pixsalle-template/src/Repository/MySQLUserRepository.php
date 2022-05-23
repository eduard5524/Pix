<?php

declare(strict_types=1);

namespace Salle\PixSalle\Repository;

use PDO;
use Salle\PixSalle\Model\User;
use Salle\PixSalle\Repository\UserRepository;

final class MySQLUserRepository implements UserRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private PDO $databaseConnection;

    public function __construct(PDO $database)
    {
        $this->databaseConnection = $database;
    }

    public function createUser(User $user): void
    {
        $query = <<<'QUERY'
        INSERT INTO users(username, email, password, createdAt, updatedAt, phone, membership, balance, userProfile, portfolio)
        VALUES(:username, :email, :password, :createdAt, :updatedAt, :phone, :membership, :balance, :userProfile, :portfolio)
        QUERY;
        $phone = "";
        $membership = "Cool";
        $username = 'user';
        $portfolio = "";
        $userProfile = "/assets/uploads/user.png";
        $statement = $this->databaseConnection->prepare($query);
        $email = $user->email();
        $balance = "30";
        $password = $user->password();
        $createdAt = $user->createdAt()->format(self::DATE_FORMAT);
        $updatedAt = $user->updatedAt()->format(self::DATE_FORMAT);
        $statement->bindParam('userProfile', $userProfile, PDO::PARAM_STR);
        $statement->bindParam('membership', $membership, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('createdAt', $createdAt, PDO::PARAM_STR);
        $statement->bindParam('updatedAt', $updatedAt, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('balance', $balance, PDO::PARAM_STR);
        $statement->bindParam('portfolio', $portfolio, PDO::PARAM_STR);
        $statement->execute();
        $user = $this->getUserByEmail($email);
        $username = 'user' . $user->id;
        $this->defaultUsername($username, $user->id);
    }

    public function getUser(){
        $query = <<<'QUERY'
        SELECT * FROM users WHERE email = :email
        QUERY;

        $email = $_SESSION['email'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();

        $count = $statement->rowCount();
        
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
        return null;
    }
    public function getPictures(){
        $query = <<<'QUERY'
        SELECT * FROM pictures
        QUERY;
        
        $statement = $this->databaseConnection->prepare($query);
        $statement->execute();
        $count = $statement->rowCount();
        
        if ($count > 0) {
            $position = 0;
            do{
                $aux = $statement->fetch(PDO::FETCH_OBJ);
                if($aux != false){
                    $row[$position]['pictures'] = $aux->src;
                    $row[$position]['author'] = $aux->author;
                    $row[$position]['id'] = $aux->id;
                    $position = $position + 1;
                }
            }while($aux != false);
        
            return $row;
        }
        return null;

    }
    # This function checks if it's available to update the user plan.
    public function checkChangePlan(){
        $query = <<<'QUERY'
        SELECT * FROM users WHERE id = :id
        QUERY;

        $id = $_SESSION['user_id'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();

        $count = $statement->rowCount();
        
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
        return null;
    }

    public function updatePlan(string $membership){
        $query = <<<'QUERY'
        UPDATE users SET membership = :membership WHERE id = :id
        QUERY;

        $id = $_SESSION['user_id'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('membership', $membership, PDO::PARAM_STR);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
   
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
    }
    public function getPasswordByEmail(){
        $query = <<<'QUERY'
        SELECT * FROM users WHERE email = :email
        QUERY;

        $email = $_SESSION['email'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();

        $count = $statement->rowCount();
        
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
        return null;
    }

    public function updateBalance(string $email, string $amount){
        # Get the current balance & update.
        $user = $this->getUserByEmail($email);
        $balance = $user->balance + $amount;
        
        # Update the balance.
        $query = <<<'QUERY'
        UPDATE users SET balance = :balance WHERE email = :email
        QUERY;

        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('balance', $balance, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);

        $statement->execute(); 
        $count = $statement->rowCount();
   
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
    }
    public function getUserByEmail(string $email)
    {
        $query = <<<'QUERY'
        SELECT * FROM users WHERE email = :email
        QUERY;

        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->execute();

        $count = $statement->rowCount();
        
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
        return null;
    }
    public function defaultUsername(string $username, string $id){
        $query = <<<'QUERY'
        UPDATE users SET username = :username WHERE id = :id
        QUERY;
       
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('id', $id, PDO::PARAM_STR);

        $statement->execute(); 
        $count = $statement->rowCount();
   
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
    }
    public function updatePassword(string $password, string $id)
    {
        $query = <<<'QUERY'
        UPDATE users SET password = :password WHERE id = :id
        QUERY;

        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute(); 
        $count = $statement->rowCount();
   
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
    }
    public function getPortfolioTitle(){
        $query = <<<'QUERY'
        SELECT * FROM users WHERE id = :id
        QUERY;
        $id = $_SESSION['user_id'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
        
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row->portfolio;
        }
        return null;
    }
    public function verifyPhoneNumber(string $phone){
        $errors = true;
        # 9xx xxx xxx      (within Spain)
        if(strlen($phone) == 9){
            if($phone[0] == 9){
                $errors = false;
            }
        }
        # +34 9xx xxx xxx  (outside Spain)
        if(strlen($phone) == 12){
            $errors = false;
        }
        # 608 xxx xxx     (within Spain before 1998)
        if(strlen($phone) == '9'){
            if($phone[0] == '6' || $phone[0] == '7'){
                $error_code = false;
                for($count = 0; $count < 9; $count++){
                    if($phone[$count] < '1' || $phone[$count] > '9'){
                        $errors = false;
                    }                      
                }
                
                $errors = false;
            }
        }
        # +34 608 xxx xxx (since 1998)[10]
        if(strlen($phone) == 11){
            if($phone[0] == 3 && $phone[1] == 4 && $phone[2] == 6 && $phone[3] == 0 && $phone[4] == 8){
                $errors = false;
            }
        }        
        return $errors;
    }
    public function updateTitle(string $portfolio){
        if(!empty($portfolio)){
            $query = <<<'QUERY'
            UPDATE users SET portfolio = :portfolio WHERE id = :id
            QUERY; 
            $id = $_SESSION['user_id'];
            $statement = $this->databaseConnection->prepare($query);
            $statement->bindParam('id', $id, PDO::PARAM_STR);
            $statement->bindParam('portfolio', $portfolio, PDO::PARAM_STR);
            $statement->execute(); 
            $count = $statement->rowCount();
            if ($count > 0) {
                $row = $statement->fetch(PDO::FETCH_OBJ);
                return $row;
            }        
        }
    }   
    public function updateProfile(string $username, string $phone, string $id, string $userProfile)
    {
        $errors_phone = $this->verifyPhoneNumber($phone);
        if($errors_phone == false){
            $query = <<<'QUERY'
            UPDATE users SET username = :username, phone = :phone, userProfile = :userProfile WHERE id = :id
            QUERY; 
            $statement = $this->databaseConnection->prepare($query);
            $statement->bindParam('phone', $phone, PDO::PARAM_STR);

            # Update session variable.
            $_SESSION['phone'] = $phone;
        }else{
            $query = <<<'QUERY'
            UPDATE users SET username = :username, userProfile = :userProfile WHERE id = :id
            QUERY;
            $statement = $this->databaseConnection->prepare($query);
        }
        $statement->bindParam('userProfile', $userProfile, PDO::PARAM_STR);
        $statement->bindParam('username', $username, PDO::PARAM_STR);        
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute(); 
        $count = $statement->rowCount();
   
        # Update the Session variables.
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $id;

        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
    }
    public function createNewAlbum($title){
        $query = <<<'QUERY'
        INSERT INTO albums (name, user_id, qr_code) VALUES (:name, :user_id, :qr_code)
        QUERY;
        $user_id = $_SESSION['user_id'];
        $id = $user_id;
        $name = $title;
        $qr_code = 'false';
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('user_id', $user_id, PDO::PARAM_STR);
        $statement->bindParam('name', $name, PDO::PARAM_STR);  
        $statement->bindParam('qr_code', $qr_code, PDO::PARAM_STR);
        $statement->execute();
    }
    public function getAlbumPhotosById(string $album_id){
        $query = <<<'QUERY'
        SELECT * FROM pictures WHERE album_id = :album_id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('album_id', $album_id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();

        if ($count > 0) {
            $position = 0;
            do{
                $aux = $statement->fetch(PDO::FETCH_OBJ);
                if($aux != false){
                    if($aux->user_id == $_SESSION['user_id']){
                        $row[$position]['src'] = $aux->src;
                        $row[$position]['author'] = $aux->author;
                        $row[$position]['album_id'] = $aux->album_id;
                        $row[$position]['id'] = $aux->id;
                        $position = $position + 1;    
                    }
                }
            }while($aux != false);
        
            return $row;
        }
        return null;
    }
    public function getAllBlogs()
    {
        $query = <<<'QUERY'
        SELECT * FROM blogs
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->execute();
        $count = $statement->rowCount();

        if ($count > 0) {
            $position = 0;
            do{
                $aux = $statement->fetch(PDO::FETCH_OBJ);
                if($aux != false){
                    $row[$position]['id'] = $aux->id;
                    $row[$position]['title'] = $aux->title;
                    $row[$position]['content'] = $aux->content;
                    $row[$position]['userID'] = $aux->userID;
                    $position = $position + 1;
                }
            }while($aux != false);
            return $row;
        }
        return null;

    }
    public function blogIDExists(string $id) {
        $query = <<<'QUERY'
        SELECT * FROM blogs WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->execute();
        $count = $statement->rowCount();

        if ($count > 0) {
            $aux = $statement->fetch(PDO::FETCH_OBJ);
            var_dump($aux);
            return true;
        }else{
            return false;
        }
    }
    public function getAlbum(string $id) {
        $query = <<<'QUERY'
        SELECT * FROM albums WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
        
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }
        return null;
    }
    public function getAlbumsById()
    {
        $query = <<<'QUERY'
        SELECT * FROM albums WHERE user_id = :user_id
        QUERY;
        $id = $_SESSION['user_id'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('user_id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
        
        if ($count > 0) {
            $position = 0;
            do{
                $aux = $statement->fetch(PDO::FETCH_OBJ);
                if($aux != false){
                    $row[$position]['id'] = $aux->id;
                    $row[$position]['name'] = $aux->name;
                    $row[$position]['qr_code'] = $aux->qr_code;
                    $position = $position + 1;
                }
            }while($aux != false);
            return $row;
        }
        return null;
    }    
    public function deleteAlbumByID(string $id) {
        $query = <<<'QUERY'
        DELETE FROM pictures WHERE album_id = :album_id
        QUERY;
        $album_id = $id;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('album_id', $album_id, PDO::PARAM_STR);
        $statement->execute();

        $query = <<<'QUERY'
        DELETE FROM albums WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
    }
    public function deleteAlbum(string $id) {
        $query = <<<'QUERY'
        DELETE FROM pictures WHERE id = :id
        QUERY;  
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
    }
    public function uploadAlbumPhoto(string $filename, string $album_id) {
        $query = <<<'QUERY'
        INSERT INTO pictures(src, author, user_id, album_id)
        VALUES(:src, :author, :user_id, :album_id)
        QUERY;
        $src = $filename;
        $author = $_SESSION['email'];
        $user_id = $_SESSION['user_id'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('src', $src, PDO::PARAM_STR);
        $statement->bindParam('author', $author, PDO::PARAM_STR);
        $statement->bindParam('user_id', $user_id, PDO::PARAM_STR);
        $statement->bindParam('album_id', $album_id, PDO::PARAM_STR);
        $statement->execute();

    }
    public function uploadQRCodeByID(string $qr_code, string $id)
    {
        $query = <<<'QUERY'
        UPDATE albums SET qr_code = :qr_code WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('qr_code', $qr_code, PDO::PARAM_STR);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute(); 
        $count = $statement->rowCount();

        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        } 
    }
    public function getQRCodeByID(string $id) 
    {
        $query = <<<'QUERY'
        SELECT * FROM albums WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
        if ($count > 0) {
            $row = $statement->fetch(PDO::FETCH_OBJ);
            return $row;
        }    
        return null;
    }
    # Blogs
    public function insertBlog(array $data)
    {
        $query = <<<'QUERY'
        INSERT INTO blogs(title, content, userID) VALUES (:title, :content, :userID)
        QUERY;
        $title = $data['title'];
        $content = $data['content'];
        $userID = $data['userID'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('title', $title, PDO::PARAM_STR);
        $statement->bindParam('content', $content, PDO::PARAM_STR);
        $statement->bindParam('userID', $userID, PDO::PARAM_STR);
        $statement->execute();
        return $this->databaseConnection->lastInsertId();
        
        
    }
    public function deleteBlogByID(string $id)
    {
        $query = <<<'QUERY'
        DELETE FROM blogs WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        return $statement->rowCount();
    }
    public function getBlogByID(string $id) 
    {
        $query = <<<'QUERY'
        SELECT * FROM blogs WHERE id = :id
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
        if ($count > 0) {
            $aux = $statement->fetch(PDO::FETCH_OBJ);
            $row['id'] = $aux->id;
            $row['title'] = $aux->title;
            $row['content'] = $aux->content;
            $row['userID'] = $aux->userID;
            return $row;
        }
        return null;
    }
    public function getBlogs() 
    {
        $query = <<<'QUERY'
        SELECT * FROM blogs
        QUERY;
        $statement = $this->databaseConnection->prepare($query);
        $statement->execute();
        $count = $statement->rowCount();
        
        if ($count > 0) {
            $position = 0;
            do{
                $aux = $statement->fetch(PDO::FETCH_OBJ);
                if($aux != false){
                    $row[$position]['id'] = $aux->id;
                    $row[$position]['title'] = $aux->title;
                    $row[$position]['content'] = $aux->content;
                    $row[$position]['userID'] = $aux->userID;
                    $position = $position + 1;
                }
            }while($aux != false);
            return $row;
        }
        return null;     
    }
    public function putBlog(array $data)
    {
        $query = <<<'QUERY'
        UPDATE blogs SET title = :title, content = :content, userID = :userID WHERE id = :id
        QUERY;
        $id = $data['id'];
        $title = $data['title'];
        $content = $data['content'];
        $userID = $data['userID'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->bindParam('title', $title, PDO::PARAM_STR);
        $statement->bindParam('content', $content, PDO::PARAM_STR);
        $statement->bindParam('userID', $userID, PDO::PARAM_STR);
        $statement->execute();
        return $statement->rowCount();
    }
    public function updateWalletAmount()
    {
    # First I check if there's enough money to create an Album.
        $query = <<<'QUERY'
        SELECT * FROM users WHERE id = :id
        QUERY;
        $id = $_SESSION['user_id'];
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindParam('id', $id, PDO::PARAM_STR);
        $statement->execute();
        $count = $statement->rowCount();
        if ($count > 0) {
            $aux = $statement->fetch(PDO::FETCH_OBJ);
            $balance = $aux->balance - 2; 
            
            if($balance > 0){
                # Update the wallet balance.
                $query = <<<'QUERY'
                UPDATE users SET balance = :balance WHERE id = :id
                QUERY;
                $id = $_SESSION['user_id'];
                $statement = $this->databaseConnection->prepare($query);
                $statement->bindParam('balance', $balance, PDO::PARAM_STR);
                $statement->bindParam('id', $id, PDO::PARAM_STR);
                $statement->execute(); 
                return true;
            }else{
                return false;    
            }
        }
        return false;
    }
}