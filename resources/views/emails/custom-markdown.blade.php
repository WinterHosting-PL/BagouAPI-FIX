
    <div style="padding: 20px; text-align: center;">
      <h2 style="color: #0072c6; text-align: center;">
        Bagou450 licensing system
      </h2>
      <p>Hello {{$username}}.</p>
      <p>Thank you for purchasing our products.<br/> We are pleased to provide you with your license key, which will allow you to use our product on two panel. <br/>Please find your license key below:</p>
     <div style="">
      @foreach($licenses as $license)
        <p>{{$license['fullname']}}: <strong>{{$license['transaction']}}</strong></p>
      @endforeach
      </div>
      <p>Don't hesitate to contact us if you have any questions or requests.</p>
      <p>Sincerely,</p>
      <p>Bagou450 Team</p>
    </div>
    <footer style=" text-align: center; padding: 10px;">
      <p>For more information or to contact us, please visit our <a href="https://bagou450.xyz/contact" style="color: #0072c6;">contact page</a>.</p>
      <p>Copyright <?php echo date("Y"); ?> Bagou450 development</p>
    </footer>


