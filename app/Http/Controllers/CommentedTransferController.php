<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class TransferController extends Controller
{
    public function index(){
    	// Running query with default connection.
		$oldUserArray = DB::table('users')->get();
		$newDatabase = DB::connection('mysql2');
		//var_dump($oldUserArray[0]);

		foreach($oldUserArray as $oldUser){

			/*****************/
			/**** ADRESS ****/
			/***************/

			//Insert Adresses in de nieuwe database en haalt gelijk het ID op
			$newAdressID = $newDatabase->table('addresses')->insertGetId(
				['street' => $oldUser->street,
				 'house_number' => $oldUser->house_number,
				 'postcode' => $oldUser->postcode]);

			echo "----****INSERT ADRESS****----<br>";
			echo "AdressID: ".$newAdressID."<br>";
			echo $oldUser->street."<br>";
			echo $oldUser->house_number."<br>";
			echo $oldUser->postcode."<br>";
			echo "----********----<br>";

			/*****************/
			/**** PROFILE****/
			/***************/

			//Split de naam op spaties
			$oldName = $oldUser->name;
			$explodedName = explode(" ", $oldUser->name);
			//telt hoe lang de exploded array is
			$explodedNameCount = count($explodedName);

			//Voornaam staat altijd op plaats 0
			$firstName = $explodedName[0];

			/*
			Als de array count niet gelijk aan 2 dan is er een tussenvoegsel in de naam en schuift de achternaam 1 plaats op, zonder tussenvoegsel is het 
			$explodedName[0] = voornaam,
			$explodedName[1] = achternaam
			*/
			if($explodedNameCount != 2){
				$insertion = $explodedName[1];
				$lastname = $explodedName[2];
			}else{
				$insertion = NULL;
				$lastname = $explodedName[1];
			}

			$newProfileID = $newDatabase->table('profiles')->insertGetId(
				['first_name' => $firstName,
				 'insertion' => $insertion,
				 'last_name' => $lastname]);

			echo "----****INSERT PROFILE****----<br>";
			echo "ProfileID: ".$newProfileID."<br>";
			echo $firstName."<br>";
			echo $insertion."<br>";
			echo $lastname."<br>";
			echo "----********----<br>";

			/*****************/
			/**** ROLES  ****/
			/***************/

			//Er word in de nieuwe database gezocht op de role van de user
			$oldUsersRole = $oldUser->role;
			$newRoles = $newDatabase->table('roles')->where('name', '=', $oldUsersRole)->first();
			$newRoleID;

			//Als de role niet gevonde is word hij in de database gezet en ID in $newRoleID gezet.
			//Als hij wel gevonden is word hij uit de database gehaald en ID in $newRoleID gezet.
			if($newRoles == NULL)
			{
				echo "ROLE DOES NOT EXIST <br>";
				$newRoleID = $newDatabase->table('roles')->insertGetId(['name' => $oldUsersRole]);
			}else{
				echo "ROLE DOES EXIST <br>";
				$newRolesGet = $newDatabase->table('roles')->where('name', '=', $oldUsersRole)->first();

				$newRoleID = $newRolesGet->id;
			}


			echo "----****ROLES****----<br>";
			echo "RoleID: ".$newRoleID."<br>";
			echo "----********----<br>";


			/*****************/
			/**** USERS  ****/
			/***************/

			$newUserID = $newDatabase->table('users')->insertGetId(
				['email' => $oldUser->email,
				 'password' => $oldUser->password,
				 'Address_id' => $newAdressID,
				 'Profile_id' => $newProfileID,
				 'Role_id' => $newRoleID]);

			echo "----****USER INSERTED****----<br>";
			echo "UserID: ".$newUserID."<br>";
			echo "email: ".$oldUser->email."<br>";
			echo "password: ".$oldUser->password."<br>";
			echo "Address_id: ".$newAdressID."<br>";
			echo "Profile_id: ".$newProfileID."<br>";
			echo "Role_id: ".$newRoleID."<br>";
			echo "----********----<br>";



		}//End oldUserForeach

		$oldBlogArray = DB::table('blog')->get();

		foreach($oldBlogArray as $oldBlog){
			$oldBlogUserID = $oldBlog->Users_id;

			//Zoekt de oude user informatie op met behulp van de User_id uit Blog tabel.
			//De email uit users tabel heb je nodig om het nieuwe ID te vinden
			$oldUserGet = DB::table('users')->where('id', '=', $oldBlogUserID)->first();

			echo "----****OLD USER INFO****----<br>";
			echo "ID: ".$oldUserGet->id."<br>";
			echo "Name: ".$oldUserGet->name."<br>";
			echo "Email: ".$oldUserGet->email."<br>";
			echo "----********----<br>";

			//Zoekt met behulp van het email adress uit de oude database de informatie die we nodig hebben, in dit geval het ID om bij de blog te kunnen linken
			$newUserGet = $newDatabase->table('users')->where('email', '=', $oldUserGet->email)->first();

			echo "----****NEW USER INFO****----<br>";
			echo "ID: ".$newUserGet->id."<br>";
			echo "Email: ".$newUserGet->email."<br>";
			echo "----********----<br>";

			//Gebruikt de gevonde userID om hem te linken in de database
			$newBlogID = $newDatabase->table('blogs')->insertGetId(
				['title' => $oldBlog->title,
				 'content' => $oldBlog->content,
				 'User_id' => $newUserGet->id]);

			echo "----****INSERTED BLOG****----<br>";
			echo "ID: ".$newBlogID."<br>";
			echo "By: ".$newUserGet->id."<br>";
			echo "----********----<br>";


		}//End oldBlogForeach

		$oldCommentsArray = DB::table('comment')->get();

		foreach($oldCommentsArray as $oldComment){
			$oldBlogID = $oldComment->Blog_id;
			$oldBlogAuthor = $oldComment->author;
			//var_dump($oldComment);

			//Haalt oude blog informatie op omdat je de titel nodig heb om de nieuwe ID op te kunnen halen
			$oldBlogsGet = DB::table('blog')->where('id', '=', $oldBlogID)->first();
			$oldBlogTitle = $oldBlogsGet->title;

			echo "----****OLD BLOG ID****----<br>";
			echo "ID: ".$oldBlogsGet->id."<br>";
			echo "Title: ".$oldBlogsGet->title."<br>";
			echo "----********----<br>";

			$newBlogsGet = $newDatabase->table('blogs')->where('title', '=', $oldBlogTitle)->first();

			echo "----****NEW BLOG ID****----<br>";
			echo "ID: ".$newBlogsGet->id."<br>";
			echo "Title: ".$newBlogsGet->title."<br>";
			echo "----********----<br>";

			//Haalt de oude user op met behulp van de naam omdat je het email adress nodig heb om de nieuwe op te halen in de nieuwe database
			$oldCommentUserGet = DB::table('users')->where('name', '=', $oldBlogAuthor)->first();

			echo "----****OLD COMMENT USER****----<br>";
			echo "ID: ".$oldCommentUserGet->id."<br>";
			echo "Email: ".$oldCommentUserGet->email."<br>";
			echo "----********----<br>";

			//Haalt met behulp van het oude email adress de informatie van de user op
			$newCommentUserGet = $newDatabase->table('users')->where('email', '=', $oldCommentUserGet->email)->first();

			echo "----****NEW COMMENT USER****----<br>";
			echo "ID: ".$newCommentUserGet->id."<br>";
			echo "Email: ".$newCommentUserGet->email."<br>";
			echo "----********----<br>";

			//Insert de comment vanuit de oude database en linkt ze met de nieuwe User en Blog ID
			$newBlogID = $newDatabase->table('comments')->insertGetId(
			 	['text' => $oldComment->text,
			 	 'Blog_id' => $newBlogsGet->id,
			 	 'User_id' => $newCommentUserGet->id]);

			echo "----****INSERT COMMENT****----<br>";
			echo "TEXT: ".$oldComment->text."<br>";
			echo "BLOGID: ".$newBlogsGet->id."<br>";
			echo "USERID: ".$newCommentUserGet->id."<br>";
			echo "----********----<br>";


		}//End oldCommentForeach

		$oldFilesArray = DB::table('file')->get();

		foreach($oldFilesArray as $oldFile){
			$oldUploadedBy = $oldFile->uploaded_by;

			//Haalt de oude informatie van de user op met behulp van uploaded_by zodat je bij de email kan voor de nieuwe database.
			$oldFileUserGet = DB::table('users')->where('name', '=', $oldUploadedBy)->first();

			// //Haalt met behulp van het oude email adress de informatie van de user op
			$newFileUserGet = $newDatabase->table('users')->where('email', '=', $oldFileUserGet->email)->first();

			echo "----****NEW FILE USER****----<br>";
			echo "ID: ".$newFileUserGet->id."<br>";
			echo "Email: ".$newFileUserGet->email."<br>";
			echo "----********----<br>";


			//Insert de comment vanuit de oude database en linkt ze met de nieuwe User en Blog ID
			$newFilesID = $newDatabase->table('files')->insertGetId(
			 	['filename' => $oldFile->filename,
			 	 'User_id' => $newFileUserGet->id]);

			echo "----****INSERT FILE****----<br>";
			echo "FILENAME: ".$oldFile->filename."<br>";
			echo "USERID: ".$newFileUserGet->id."<br>";
			echo "----********----<br>";


		}//End oldFilesForeach


    }//End Function
}
