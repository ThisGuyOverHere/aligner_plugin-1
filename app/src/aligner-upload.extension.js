(function() {
    function overrideHeader( Header ) {
        if ( Header ) {
            Header.prototype.getMoreLinks = function (  ) {
                return <li><a className="bigblue" href="/plugins/aligner/index#" target="_blank">Aligner</a></li>
            }
        }
    }
    overrideHeader(Header);
})() ;