<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>OUTPATIENT LETTER OF AUTHORITY</title>

    <style>
        @page {
            margin: 10;
        }
    	body{
        	font-family: Arial, sans-serif;
            font-size: 10px;
            width: 50%
        }
        .header{
        	width: 100%
        }
        .header img {
        	float: left;
            width: 50%;
        }
        .header .info {
        	text-align: right;
            font-size: 9px;
        }
        .container-p-body {
        	text-align: justify;
            margin-top: 10px;

        }
        .container-p-body span {
        	font-size: 9px;
            line-height: 1.2;
        }
        .solid-line {
        	border-style: solid;
            margin-top: 10px;
            border-width:1px;
        }
        .main-container{
        	margin-top: 10px;
        }
        .td-title {
        	text-align: right;
            width: 25%;
        }
        .td-value {
        	font-weight: bold;
            white-space: nowrap;
        }
        /* Allow long underlined values (like Diagnosis) to wrap without creating a second underline */
        .td-value.underline {
            white-space: normal;
            word-break: break-word;
        }

        .underline {
        	display: inline-block;
            width: 100%;
            border-bottom: 1px solid #000;
            padding-top: 5px;

        }

        .underline::after {
          content: ''; /* ensures the line extends even if empty */
          width: 100%;
        }

        .underline-btm {
        	display: inline-block;
            width: 50%;
            border-bottom: 1px solid #000;
            padding-top: 5px;

        }

        .underline-btm::after {
          content: ''; /* ensures the line extends even if empty */
          width: 100%;
        }

        .signature-block {
            margin-top: 4px;
        }

        .signature-image-box {
            height: 32px;  /* was 50px */
            margin-bottom: 2px;
        }

        .signature-image-box img {
            height: 32px;
            width: auto;
            display: block;
        }


    </style>
</head>
<body>

    <div class="header">
        <img src="{{ $logo }}">
        <div class="info">
            4F Civic Prime Building Unit 405-410, Civic Drive <br>
            Filinvest, Alabang, Muntinlupa City 1781 <br>
            0917-8055424 & 0917-8855424 (calls only)<br>
            0917-8255424 & 0917-8955424 (call and text)<br>
            Fax No.: (02) 8886-4478
        </div>

    </div>

    <div style="margin-top: 5px; background-color: #e6e6e6; text-align: center; padding: 0.5px 0px; ">
    	<h2 style="font-weight: bold;" >OUTPATIENT LETTER OF AUTHORITY</h2>
        <h2 style="color: #b61a02;" >CONSULTATION</h2>
    </div>

    <div class="container-p-body">
    	<span>1. Copy of this LOA should be emailed by the doctor to medicalclaims@llibi.com or faxed to (02) 8886-4478 or sent to CREDIT & COLLECTION of the hospital to be submitted within 30 calendar days to Lacson & Lacson Insurance Brokers, Inc. for payment of consultation fee.</span><br />
    	<span>2. If laboratory / diagnostic tests are required, doctor should return a copy to patient.</span>
    </div>

    <div class="solid-line"></div>

    <table style="margin-top: 5px; width: 100%; ">
    	<tr>
        	<td class="td-title">Date and Time :</td>
            <td class="td-value">{{ $document_datetime }} / (valid for 7 days from date of issuance)</td>
        </tr>
     	<tr>
        	<td class="td-title">Doc No :</td>
            <td class="td-value">{{ $document_number }}</td>
        </tr>
     	<tr>
        	<td class="td-title">Consultation Date :</td>
            <td class="td-value underline"></td>
        </tr>
    </table>

    <table style="margin-top: 5px; width: 100%; ">
    	<tr>
        	<td class="td-title">To :</td>
            <td class="td-value" style="width:40%;">Dr. {{ $doctor_name }} </td>
            <td><img src="{{ $confidential }}" style="width:15%; position:absolute;"> </td>
        </tr>
    	<tr>
        	<td class="td-title"></td>
            <td class="" style="font-size:8px;"> {{ $hospital_name }} </td>
        </tr>
    </table>

    <table style="margin-top: 5px; width: 100%; ">
    	<tr>
        	<td class="td-title">Re :</td>
            <td class="td-value">{{ $employee_name }}</td>
        </tr>
     	<tr>
        	<td></td>
            <td style="font-size:8px;">BOUNTY FRESH FOOD, INC. - DEP.</td>
        </tr>
     	<tr>
        	<td></td>
            <td style="color:#b61a02; font-weight:bold;" >(PATIENT) {{ $patient_name }} </td>
        </tr>
    </table>

    <div style="border-style:dashed; margin-top:5px; width:100%; border-width:1px;"></div>

    <table style="margin-top: 5px; width: 100%;">
        <tr>
            <td style="text-align: right; width: 15%;">Complaint :</td>
            <td class="td-value underline">{{ $complaints }}</td>
        </tr>
        <tr>
            <td style="text-align: right; width: 15%;">Diagnosis :</td>
            <td class="td-value underline"></td>
        </tr>
    </table>

    <p style="margin-left: 20px;">Prescribed laboratory & Diagnostics test(s)</p>
	<table style=" width: 100%; ">
		<tr>
        	<td class="td-value underline" style="width: 100%;"></td>
        </tr>

    </table>

    <p style="text-align: center;"><i>Please honor this electronically generated LOA</i></p>

    <table style="width:100%; margin-top:10px; border-collapse:collapse;">
        <tr>
            <!-- Left Side -->
            <td style="width:50%; vertical-align:top;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                <td style="white-space:nowrap;">
                    <h4 style="margin:0; display:inline;">Professional Fee :</h4>
                </td>
                    <td style="border-bottom:1px solid #000; width:100%;">&nbsp; </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:30px;">
                        <div class="signature-block">
                            <div class="signature-image-box">
                                <img src="{{ $signature }}" alt="Signature">
                            </div>
                            <div style="font-weight:bold;">Lilibeth Y. Lacson, EVP</div>
                        </div>
                    </td>
                </tr>
            </table>
            </td>

            <!-- Right Side -->
            <td style="width:50%; vertical-align:top; text-align:center;">
            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="border-bottom:1px solid #000; width:70%; text-align:center;">
                        <span style="display:inline-block; width:100%; text-align:center;"></span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align:center; padding-top:4px;">Print Doctor's Name/Signature</td>
                </tr>
                <tr>
                    <td style="text-align:center; padding-top:30px;">
                        TIN: <span style="display:inline-block; border-bottom:1px solid #000; width:150px;"></span>
                    </td>
                </tr>

            </table>
            </td>
        </tr>
    </table>
    <p style="text-align: justify; font-size:10px;  ">This Letter of Authority (LOA) provides you access to Lacson & Lacson's network of accredited health care providers included in your insurance benefit with your employer. To serve you better, the information you will provide us such as your name, employment, age, medical results and documents will be used by our representatives/employees to process your insurance claim. In the process of helping you out with your claim, we will receive your statement of accounts, medical records, and other documents related to your use of our LOA. This information may be accessed by your employer for the purpose of reviewing your benefits or in conducting survey studies. <br />Should you have any concerns about the privacy of your information, please contact us at concerns@llibi.com or communicate it immediately with any of our client care representatives. </p>









</body>
</html>
