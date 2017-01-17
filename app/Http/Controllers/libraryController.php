<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use DB;
use App\Department;
use App\Book;
use App\Student;

class libraryController extends Controller
{
	protected $semesters=[
			'L1T1' => 'First Year 1st Semester',
			'L1T2' => 'First Year 2nd Semester',
			'L2T1' => 'Second Year 1st Semester',
			'L2T2' => 'Second Year 2nd Semester',
			'L3T1' => 'Third Year 1st Semester',
			'L3T2' => 'Third Year 2nd Semester'
	];
	public function __construct()
    {
        $this->middleware('teacher');
    }

	public function getAddbook()
	{
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		return view('library.addbook',compact('departments'));
	}


	/**
	* Show the form for creating a new resource.
	*
	* @return Response
	*/
	public function postAddbook(Request $request)
	{
		$rules=[
			'code' => 'required|max:50|unique:books,code',
			'title' => 'required|max:250',
			'author' => 'required|max:100',
			'type' => 'required',
			'department' => 'required'
		];
		$validator = \Validator::make($request->all(), $rules);
		if ($validator->fails())
		{
			return Redirect::to('/library/addbook')->withErrors($validator)->withInput();
		}
		else {

				$book = new Book();
				$book->code = $request->get('code');
				$book->title = $request->get('title');
				$book->author = $request->get('author');
				$book->quantity = $request->get('quantity');
				$book->rackNo = $request->get('rackNo');
				$book->rowNo = $request->get('rowNo');
				$book->type = $request->get('type');
				$book->department_id = $request->get('department');
				$book->desc = $request->get('desc');
				$book->save();
				$notification= array('title' => 'Data Store', 'body' => 'Book added succesfully.');
				return Redirect::to('/library/addbook')->with("success", $notification);


		}

	}


	/**
	* Store a newly created resource in storage.
	*
	* @return Response
	*/
	public function getviewbook(Request $request)
	{
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$department = "";
		if($request->has('department')){
			$department = $request->get('department');
		}
		$books = DB::table('books')
		->join('department', 'books.department_id', '=', 'department.id')
		->select('books.id', 'books.code', 'books.title', 'books.author','books.quantity','books.rackNo','books.rowNo','books.type','books.desc','department.name as department')
		->where('books.department_id',$department)
		->where('books.deleted_at',NULL)
		->paginate(50);

		return view('library.booklist',compact('departments','department','books'));
	}

	/**
	* Display the specified resource.
	*
	* @param  int  $id
	* @return Response
	*/
	public function getBook($id)
	{
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$book= Book::select('*')->find($id);
		return view('library.bookedit',compact('departments','book'));
	}


	/**
	* Show the form for editing the specified resource.
	*
	* @param  int  $id
	* @return Response
	*/
	public function postUpdateBook(Request $request)
	{
		$rules=[
			//'code' => 'required|max:50',
			'title' => 'required|max:250',
			'author' => 'required|max:100',
			'type' => 'required',
			'department' => 'required'
		];
		$validator = \Validator::make($request->all(), $rules);
		if ($validator->fails())
		{
			return Redirect::to('/library/edit/'.$request->get('id'))->withErrors($validator)->withInput();
		}
		else {

			$book = Book::find($request->get('id'));
			//$book->code = $request->get('code');
			$book->title = $request->get('title');
			$book->author = $request->get('author');
			$book->quantity = $request->get('quantity');
			$book->rackNo = $request->get('rackNo');
			$book->rowNo = $request->get('rowNo');
			$book->type = $request->get('type');
			$book->department_id = $request->get('department');
			$book->desc = $request->get('desc');
			$book->save();
			$notification= array('title' => 'Data Update', 'body' => 'Book updated succesfully.');
			return Redirect::to('/library/view')->with("success",$notification);

		}

	}


	/**
	* Update the specified resource in storage.
	*
	* @param  int  $id
	* @return Response
	*/
	public function deleteBook($id)
	{
		$book = Book::find($id);
		$book->delete();
		$notification= array('title' => 'Data Delete', 'body' => 'Book deleted succesfully.');
		return Redirect::to('/library/view')->with("success", $notification);
	}

	public function getissueBook()
	{
		$semesters = $this->semesters;
		$students=[];
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		$sessions=Student::select('session','session')->distinct()->lists('session','session');
		$books = Book::select(DB::raw("CONCAT(title,'[',author,']#',code) as name,id"))->lists('name','id');
		return view('library.bookissue',compact('students','semesters','departments','sessions','','books'));
	}

	public function postissueBook()
	{

		$rules=[
			'regiNo' => 'required',
			'bookCode' => 'required',
			'quantity' => 'required',
			'issueDate' => 'required',
			'returnDate' => 'required',

		];
		$validator = \Validator::make($request->all(), $rules);
		if ($validator->fails())
		{
			return Redirect::to('/library/issuebook')->withErrors($validator)->withInput();
		}
		else {


			/*$availabeQuantity=DB::table('bookStock')->select('quantity')->where('code',$request->get('code'))->first();

			if($request->get('quantity')>$availabeQuantity->quantity)
			{
			$errorMessages = new Illuminate\Support\MessageBag;
			$errorMessages->add('deplicate', 'This book quantity not availabe right now!');
			return Redirect::to('/library/issuebook')->withErrors($errorMessages)->withInput();

		}*/
		$data=$request->all();
		$issueData = [];
		$now=\Carbon\Carbon::now();
		foreach ($data['bookCode'] as $key => $value){
			$issueData[] = [
				'regiNo' => $data['regiNo'],
				'issueDate' => $this->parseAppDate($data['issueDate']),
				'code' => $value,
				'quantity' => $data['quantity'][$key],
				'returnDate' => $this->parseAppDate($data['returnDate'][$key]),
				'fine' => $data['fine'][$key],
				'created_at' => $now,
				'updated_at' => $now,
			];

		}
		Issuebook::insert($issueData);
		/*  $issuebook = new Issuebook();
		$issuebook->code = $request->get('code');
		$issuebook->quantity = $request->get('quantity');
		$issuebook->regiNo = $request->get('regiNo');
		$issuebook->issueDate = $this->parseAppDate($request->get('issueDate'));
		$issuebook->returnDate = $this->parseAppDate($request->get('returnDate'));
		$issuebook->fine = $request->get('fine');
		$issuebook->save();*/
		return Redirect::to('/library/issuebook')->with("success","Succesfully book borrowed for '".$request->get('regiNo')."'.");

	}

}
public function getissueBookview()
{

	return view('library.bookissueview');
}
public function postissueBookview()
{

	if($request->get('status')!="")
	{
		$books = Issuebook::select('*')
		->Where('Status','=',$request->get('status'))
		->get();
		return view('library.bookissueview',compact('books'));
	}
	if($request->get('regiNo')!="" || $request->get('code') !="" || $request->get('issueDate') !="" || $request->get('returnDate') !="")
	{

		$books = Issuebook::select('*')->where('regiNo','=',$request->get('regiNo'))
		->orWhere('code','=',$request->get('code'))
		->orWhere('issueDate','=',$this->parseAppDate($request->get('issueDate')))
		->orWhere('returnDate','=',$this->parseAppDate($request->get('returnDate')))

		->get();
		return view('library.bookissueview',compact('books'));

	}
	else {

		return Redirect::to('/library/issuebookview')->with("error","Pleae fill up at least one feild!");

	}

}
public function getissueBookupdate($id)
{
	$book= Issuebook::find($id);
	return view('library.bookissueedit',compact('book'));
}
public function postissueBookupdate()
{
	$rules=[
		'regiNo' => 'required|max:20',
		'code' => 'required|max:50',
		'issueDate' => 'required',
		'returnDate' => 'required',
		'status' => 'required',

	];
	$validator = \Validator::make($request->all(), $rules);
	if ($validator->fails())
	{
		return Redirect::to('/library/issuebookupdate/'.$request->get('id'))->withErrors($validator);
	}
	else {

		$book = Issuebook::find($request->get('id'));
		$book->code = $request->get('code');
		$book->regiNo = $request->get('regiNo');
		$book->issueDate = $this->parseAppDate($request->get('issueDate'));
		$book->returnDate = $this->parseAppDate($request->get('returnDate'));
		$book->fine = $request->get('fine');
		$book->Status = $request->get('status');
		$book->save();
		return Redirect::to('/library/issuebookview')->with("success","Succesfully book record updated.");

	}
}

public function deleteissueBook($id)
{
	$book= Issuebook::find($id);
	$book->delete();
	return Redirect::to('/library/issuebookview')->with("success","Succesfully book record deleted.");
}
public function getsearch()
{
	$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
	$inputs = [
		"code"=>"",
		"title"=>"",
		"author"=>"",
		"type"=>"",
		"department"=>""
	];
	return view('library.booksearch',compact('departments','inputs'));
}
public function postsearch(Request $request)
{
	if($request->get('code')!="" || $request->get('title')!="" || $request->get('author') !="")
	{
		$query=Book::leftJoin('department', function($join) {
			$join->on('books.department_id', '=', 'department.id');

		})
		->leftJoin('stock_books','books.id', '=', 'stock_books.books_id')
		->select('books.id', 'books.code', 'books.title', 'books.author','stock_books.quantity','books.rackNo','books.rowNo','books.type','books.desc','department.name');
		if($request->get('code')!="") $query->where('books.code','=',$request->get('code'));
		if($request->get('title')!="")$query->orWhere('books.title','LIKE','%'.$request->get('title').'%');
		if($request->get('author') !="")$query->orWhere('books.author','LIKE','%'.$request->get('author').'%');


		$books=$query->get();

		$inputs = [
			"code" => $request->get('code'),
			"title" => $request->get('title'),
			"author" => $request->get('author'),
			"type" => "",
			"department" => ""
		];
		$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
		return view('library.booksearch',compact('books','departments','inputs'));

	}
	else {

		return Redirect::to('/library/search')->with("error","Pleae fill up at least one feild!");

	}
}
public function postsearch2(Request $request)
{
	$rules=[
		'type' => 'required',
		'department' => 'required',


	];
	$validator = \Validator::make($request->all(), $rules);
	if ($validator->fails())
	{
		return Redirect::to('/library/search')->withErrors($validator);
	}
	else {
			$books = DB::table('books')
			->join('department', 'books.department_id', '=', 'department.id')
			->join('stock_books','books.id', '=', 'stock_books.books_id')
			->select('books.id', 'books.code', 'books.title', 'books.author','stock_books.quantity','books.rackNo','books.rowNo','books.type','books.desc','department.name')
			->where('books.department_id',$request->get('department'))
			->where('books.type',$request->get('type'))->get();


				$inputs = [
					"code" => "",
					"title" => "",
					"author" => "",
					"type" => $request->get('type'),
					"department" => $request->get('department')
				];
				$departments = Department::select('id','name')->orderby('name','asc')->lists('name', 'id');
				return view('library.booksearch',compact('books','departments','inputs'));


	}
}

public function getReports()
{

	return view('library.libraryReports');
}

public function Reportprint($do)
{
	if($do=="today")
	{
		$todayReturn = DB::table('issueBook')
		->join('Student', 'Student.regiNo', '=', 'issueBook.regiNo')
		->join('Books','books.code','=','issueBook.code')
		->join('Class','class.code','=','Student.class')
		->select('books.title', 'books.author','books.type','issueBook.quantity','issueBook.fine','Student.firstName','Student.middleName','Student.lastName','Student.rollNo','class.name as class')
		->where('issueBook.returnDate',date('Y-m-d'))
		->where('issueBook.Status','Borrowed')
		->get();
		$rdata =array('name'=>'Today Return List','total'=>count($todayReturn));

		$datas=$todayReturn;
		$institute=Institute::select('*')->first();
		$pdf = PDF::loadView('library.libraryreportprinttex',compact('datas','rdata','institute'));
		return $pdf->stream('today-books-return-List.pdf');

	}
	else if($do=="expire")
	{
		$expires = DB::table('issueBook')
		->join('Student', 'Student.regiNo', '=', 'issueBook.regiNo')
		->join('Books','books.code','=','issueBook.code')
		->join('Class','class.code','=','Student.class')
		->select('books.title', 'books.author','books.type','issueBook.quantity','issueBook.fine','Student.firstName','Student.middleName','Student.lastName','Student.rollNo','class.name as class')
		->where('issueBook.returnDate','<',date('Y-m-d'))
		->where('issueBook.Status','Borrowed')
		->get();
		$rdata =array('name'=>'Today Expire List','total'=>count($expires));

		$datas=$expires;
		$institute=Institute::select('*')->first();
		$pdf = PDF::loadView('library.libraryreportprinttex',compact('datas','rdata','institute'));
		return $pdf->stream('books-expire-List.pdf');
	}
	else {
		$books = AddBook::select('*')->where('type',$do)->get();
		$rdata =array('name'=>$do,'total'=>count($books));

		$datas=$books;
		$institute=Institute::select('*')->first();
		$pdf = PDF::loadView('library.libraryreportbooks',compact('datas','rdata','institute'));
		return $pdf->stream('books-expire-List.pdf');
	}
	return $do;
}
public function getReportsFine()
{
	return view('library.libraryfinereport');
}
public function ReportsFineprint($month)
{
	$sqlraw="select sum(fine) as totalFine from issueBook where Status='Returned' and EXTRACT(YEAR_MONTH FROM returnDAte) = EXTRACT(YEAR_MONTH FROM '".$month."')";
	$fines = DB::select(DB::RAW($sqlraw));
	if($fines[0]->totalFine)
	{

		$total=$fines[0]->totalFine;
	}
	else
	{
		$total=0;
	}
	$institute=Institute::select('*')->first();
	$rdata =array('month'=>date('F-Y', strtotime($month)),'name'=>'Monthly Fine Collection Report','total'=>$total);
	$pdf = PDF::loadView('library.libraryfinereportprint',compact('rdata','institute'));
	return $pdf->stream('libraryfinereportprint.pdf');


}
private function  parseAppDate($datestr)
{

	if($datestr=="" or $datestr== NULL)
	return $datestr="0000-00-00";
	$date = explode('/', $datestr);
	return $date[2].'-'.$date[1].'-'.$date[0];
}

public function checkBookAvailability($code,$quantity)
{
	$availabeQuantity=DB::table('bookStock')
	->select('quantity')
	->where('code',$code)->first();
	$result = "Yes";
	if($quantity>$availabeQuantity->quantity)
	$result = "No";
	return ["isAvailable" => $result ];


}

}