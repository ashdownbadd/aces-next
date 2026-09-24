curl --url ^"http://aces.test/loans/14/payments^" ^
  -H ^"Accept: */*^" ^
  -H ^"Accept-Language: en-US,en;q=0.7^" ^
  -H ^"Connection: keep-alive^" ^
  -H ^"Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryaUaBjc7nN0mQCri9^" ^
  -b ^"PHPSESSID=gp7s1gcpro7ol7l98ev5fuf87q^" ^
  -H ^"Origin: http://aces.test^" ^
  -H ^"Referer: http://aces.test/loans/14/show^" ^
  -H ^"Sec-GPC: 1^" ^
  -H ^"User-Agent: Mozilla/5.0 ^(Windows NT 10.0; Win64; x64^) AppleWebKit/537.36 ^(KHTML, like Gecko^) Chrome/153.0.0.0 Safari/537.36^" ^
  -H ^"X-Requested-With: XMLHttpRequest^" ^
  --data-raw ^"------WebKitFormBoundaryaUaBjc7nN0mQCri9^

Content-Disposition: form-data; name=^\^"_csrf^\^"^

^

6a4793ad109804fa5865cd7e5f9c41f9af85d431897bbbdae669571c32cafd84^

------WebKitFormBoundaryaUaBjc7nN0mQCri9^

Content-Disposition: form-data; name=^\^"payment_token^\^"^

^

ad593bcfb6cdbe5090ae7da58d4a1722eebc48049900cf143b8218ef3f34196f^

------WebKitFormBoundaryaUaBjc7nN0mQCri9^

Content-Disposition: form-data; name=^\^"amount_paid^\^"^

^

100^

------WebKitFormBoundaryaUaBjc7nN0mQCri9^

Content-Disposition: form-data; name=^\^"remarks^\^"^

^

^

------WebKitFormBoundaryaUaBjc7nN0mQCri9--^

^" ^
  --insecure