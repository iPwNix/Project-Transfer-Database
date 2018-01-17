<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class TransferController extends Controller
{
    public function index(){
    	//Normale database (Oude)
		$oldUserArray = DB::table('users')->get();
		//Nieuwe database (Nieuw)
		$newDatabase = DB::connection('mysql2');

		foreach($oldUserArray as $oldUser){

			/*****************/
			/**** ADRESS ****/
			/***************/

			//Insert Adresses in de nieuwe database en haalt gelijk het ID op
			$newAdressID = $newDatabase->table('addresses')->insertGetId(
				['street' => $oldUser->street,
				 'house_number' => $oldUser->house_number,
				 'postcode' => $oldUser->postcode]);

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
				$newRoleID = $newDatabase->table('roles')->insertGetId(['name' => $oldUsersRole]);
			}else{
				$newRolesGet = $newDatabase->table('roles')->where('name', '=', $oldUsersRole)->first();

				$newRoleID = $newRolesGet->id;
			}

			/*****************/
			/**** USERS  ****/
			/***************/

			$newUserID = $newDatabase->table('users')->insertGetId(
				['email' => $oldUser->email,
				 'password' => $oldUser->password,
				 'Address_id' => $newAdressID,
				 'Profile_id' => $newProfileID,
				 'Role_id' => $newRoleID]);
			echo "**************************<br>";
			echo "****NEW USER INSERTED*****<br>";
			echo "**************************<br>";

		}//End oldUserForeach

		$oldBlogArray = DB::table('blog')->get();

		foreach($oldBlogArray as $oldBlog){
			$oldBlogUserID = $oldBlog->Users_id;

			//Zoekt de oude user informatie op met behulp van de User_id uit Blog tabel.
			//De email uit users tabel heb je nodig om het nieuwe ID te vinden
			$oldUserGet = DB::table('users')->where('id', '=', $oldBlogUserID)->first();

			//Zoekt met behulp van het email adress uit de oude database de informatie die we nodig hebben, in dit geval het ID om bij de blog te kunnen linken
			$newUserGet = $newDatabase->table('users')->where('email', '=', $oldUserGet->email)->first();

			//Gebruikt de gevonde userID om hem te linken in de database
			$newBlogID = $newDatabase->table('blogs')->insertGetId(
				['title' => $oldBlog->title,
				 'content' => $oldBlog->content,
				 'User_id' => $newUserGet->id]);

			echo "**************************<br>";
			echo "****NEW BLOG INSERTED*****<br>";
			echo "**************************<br>";

		}//End oldBlogForeach

		$oldCommentsArray = DB::table('comment')->get();

		foreach($oldCommentsArray as $oldComment){
			$oldBlogID = $oldComment->Blog_id;
			$oldBlogAuthor = $oldComment->author;

			//Haalt oude blog informatie op omdat je de titel nodig heb om de nieuwe ID op te kunnen halen
			$oldBlogsGet = DB::table('blog')->where('id', '=', $oldBlogID)->first();
			$oldBlogTitle = $oldBlogsGet->title;

			$newBlogsGet = $newDatabase->table('blogs')->where('title', '=', $oldBlogTitle)->first();

			//Haalt de oude user op met behulp van de naam omdat je het email adress nodig heb om de nieuwe op te halen in de nieuwe database
			$oldCommentUserGet = DB::table('users')->where('name', '=', $oldBlogAuthor)->first();

			//Haalt met behulp van het oude email adress de informatie van de user op
			$newCommentUserGet = $newDatabase->table('users')->where('email', '=', $oldCommentUserGet->email)->first();

			//Insert de comment vanuit de oude database en linkt ze met de nieuwe User en Blog ID
			$newBlogID = $newDatabase->table('comments')->insertGetId(
			 	['text' => $oldComment->text,
			 	 'Blog_id' => $newBlogsGet->id,
			 	 'User_id' => $newCommentUserGet->id]);
			echo "**************************<br>";
			echo "****NEW COMMENT INSERTED*****<br>";
			echo "**************************<br>";

		}//End oldCommentForeach

		$oldFilesArray = DB::table('file')->get();

		foreach($oldFilesArray as $oldFile){
			$oldUploadedBy = $oldFile->uploaded_by;

			//Haalt de oude informatie van de user op met behulp van uploaded_by zodat je bij de email kan voor de nieuwe database.
			$oldFileUserGet = DB::table('users')->where('name', '=', $oldUploadedBy)->first();

			// //Haalt met behulp van het oude email adress de informatie van de user op
			$newFileUserGet = $newDatabase->table('users')->where('email', '=', $oldFileUserGet->email)->first();

			//Insert de comment vanuit de oude database en linkt ze met de nieuwe User en Blog ID
			$newFilesID = $newDatabase->table('files')->insertGetId(
			 	['filename' => $oldFile->filename,
			 	 'User_id' => $newFileUserGet->id]);

			echo "**************************<br>";
			echo "****NEW FILE INSERTED*****<br>";
			echo "**************************<br>";

		}//End oldFilesForeach


    }//End Function
}
