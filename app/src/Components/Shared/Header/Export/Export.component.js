import React from 'react';
import SystemActions from "../../../../Actions/System.actions";

const Export = () => (
    <div>
        <button className="ui primary button"
                onClick={() => SystemActions.setExportModalStatus(true)}>
            <span>
                Download TMX
                <i aria-hidden='true' className="upload icon"></i>
            </span>
        </button>
    </div>
);

export default Export;