function ajaxCall(url, data = {}, type = "POST", headers = { 'Content-Type' : ['application/json']}){

    return new Promise((resolve, reject) => {

        var httpRequest = new XMLHttpRequest();

        if(!httpRequest){
            reject("Błąd: Nie moge stworzyć instancji obiektu XMLHTTP");
        }

        httpRequest.onreadystatechange = () => {
            if (httpRequest.readyState === httpRequest.DONE) {
                if (httpRequest.status === 200) {
                    resolve(httpRequest.responseText);
                } else {
                    reject("Błąd: Problem z żadaniem");
                }
            }
        };

        httpRequest.open(type, url);
        for (const [header, values] of Object.entries(headers)) {
            values.forEach(value => {
                httpRequest.setRequestHeader(header, value);
            });
        };

        if(data){
            console.log("Sending data:");
            console.log(JSON.stringify(data));
            httpRequest.send(JSON.stringify(data));
        }else{
            httpRequest.send();
        }
        

    });
    
}
