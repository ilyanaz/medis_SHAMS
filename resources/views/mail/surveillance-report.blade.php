<div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.5;color:#111827;">
    <p>To whom it may concern,</p>

    <p>Please refer attachment of Medical Surveillance Report for patient named as below:</p>

    <p style="margin:0 0 20px;">
        <strong>Name:</strong> {{ $mailData['patient_name'] ?? '-' }}<br>
        <strong>IC/Passport Number:</strong> {{ $mailData['identity_no'] ?? '-' }}<br>
        <strong>Exam:</strong> Medical Surveillance<br>
        <strong>Date:</strong> {{ $mailData['exam_date'] ?? '-' }}
    </p>

    <p style="margin:0;">
        Klinik Haydar &amp; Kamal<br>
        5405-B, Jalan Kuala Krai,<br>
        15150, Kota Bharu<br>
        Kelantan.
    </p>
</div>
