<?php
namespace App\Enums;
enum PaperStatus:string { case Uploaded='UPLOADED'; case Processing='PROCESSING'; case Analyzed='ANALYZED'; case Failed='FAILED'; case Archived='ARCHIVED'; }
