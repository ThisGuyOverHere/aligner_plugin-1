import React from 'react';
import SystemActions from "../../../../Actions/System.actions";

const Export = () => (
    <div>
        <button className=""
                onClick={() => SystemActions.setExportModalStatus(true)}>
            <span>
                Download TMX
                <i aria-hidden='true' className="download icon"></i>
            </span>
        </button>
    </div>
);

export default Export;