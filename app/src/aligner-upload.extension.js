(function() {
    function overrideHeader( Header ) {
        if ( Header ) {
            Header.prototype.openAligner = function (  ) {
                $(document).trigger('aligner-click');
            };
            Header.prototype.getMoreLinks = function (  ) {
                return <li><a className="bigblue" href="/plugins/aligner/index#" target="_blank" onClick={()=>this.openAligner()}>Aligner</a></li>
            }
        }
    }
    overrideHeader(Header);
})() ;