import React from 'react';
import SystemActions from "../../../../Actions/System.actions";

const Export = () => (
    <div>
        <button className=""
                onClick={() => SystemActions.setExportModalStatus(true)}>
            <span>
                 <i aria-hidden='true' className="download icon"/>
                Download TMX
            </span>
        </button>
    </div>
);

export default Export;