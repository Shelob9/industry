function Industry( $, INDUSTRY, $content ){

    var self = this;
    this.bindAll = function(){
        $(document).click(function(e) {
            self.clickHandler(e);
        });
    };
    this.isExternal = function(url) {
        var domain = function(url) {
            return url.replace('http://','').replace('https://','').split('/')[0];
        };

        return domain(location.href) !== domain(url);
    };

    this.clickHandler = function( e){

        var el = e.target;
        var href = el.getAttribute( 'href' );

        var id = el.getAttribute( 'id' );
        if( 'undefined' == href || null == href ) {
            return;
        }

        //releasing too early possibly
        e.preventDefault();
        self.request( href, e );

    };

    this.addParam = function setParam(url, param, value) {
        var re = new RegExp("[\\?&]" + param + "=([^&#]*)"), match = re.exec(url), delimiter, newString;

        if (match === null) {
            // append new param
            var hasQuestionMark = /\?/.test(url);
            delimiter = hasQuestionMark ? "&" : "?";
            newString = url + delimiter + param + "=" + value;
        } else {
            delimiter = match[0].charAt(0);
            newString = url.replace(re, delimiter + param + "=" + value);
        }

        return newString;
    };
    
    this.request = function ( href, e ) {
        var url = self.addParam( href, INDUSTRY.nonce.key, INDUSTRY.nonce.value );
        url = self.addParam( url, INDUSTRY.flag.key, INDUSTRY.flag.value );
        $.get( url ).success( function ( r ) {
            if( 'object' == typeof r && undefined != r.data && undefined != r.data.html ){
                //Yah this fade out sucks, but it shows off that it worked
                $content.fadeOut().html( r.data.html ).fadeIn();
                //@TODO Set title
                history.pushState({}, '', href );
            }
        });
    }

}

jQuery( document ).ready( function ( $ ) {
    var industry = new Industry( $, INDUSTRY, $( '#genesis-content' ) );
    industry.bindAll();


});