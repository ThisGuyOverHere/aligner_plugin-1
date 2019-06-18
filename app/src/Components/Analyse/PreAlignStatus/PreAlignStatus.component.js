import React from 'react';

const PreAlignStatus = (props) => (
    <div>

        <h3>{props.actualPhaseName ? props.stopped ? 'Waiting...' : props.actualPhaseName == "not_started" ? "Preparing Project..." : 'Aligning...' : '' } </h3>
        <div className="bar-container">
            <div className='ui progress' data-percent={props.progress}>
                <div className='bar' style={{width: props.progress + '%'}}/>
            </div>
            <div className="percentage">{props.progress + '%'}</div>
        </div>
    </div>

);

export default PreAlignStatus;
