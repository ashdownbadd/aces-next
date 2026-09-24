curl --url ^"http://aces.test/loans/14/payments^" ^
  -H ^"Accept: */*^" ^
  -H ^"Accept-Language: en-US,en;q=0.7^" ^
  -H ^"Connection: keep-alive^" ^
  -H ^"Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryb0D2be8uadIK62hi^" ^
  -b ^"PHPSESSID=gp7s1gcpro7ol7l98ev5fuf87q^" ^
  -H ^"Origin: http://aces.test^" ^
  -H ^"Referer: http://aces.test/loans/14/show^" ^
  -H ^"Sec-GPC: 1^" ^
  -H ^"User-Agent: Mozilla/5.0 ^(Windows NT 10.0; Win64; x64^) AppleWebKit/537.36 ^(KHTML, like Gecko^) Chrome/153.0.0.0 Safari/537.36^" ^
  -H ^"X-Requested-With: XMLHttpRequest^" ^
  --data-raw ^"------WebKitFormBoundaryb0D2be8uadIK62hi^

Content-Disposition: form-data; name=^\^"_csrf^\^"^

^

6a4793ad109804fa5865cd7e5f9c41f9af85d431897bbbdae669571c32cafd84^

------WebKitFormBoundaryb0D2be8uadIK62hi^

Content-Disposition: form-data; name=^\^"payment_token^\^"^

^

67475d8bac1db3dfc743cc4676fef0ffd3465a630d6fbbbc2c6553399ce48885^

------WebKitFormBoundaryb0D2be8uadIK62hi^

Content-Disposition: form-data; name=^\^"amount_paid^\^"^

^

100^

------WebKitFormBoundaryb0D2be8uadIK62hi^

Content-Disposition: form-data; name=^\^"remarks^\^"^

^

^

------WebKitFormBoundaryb0D2be8uadIK62hi--^

^" ^
  --insecure