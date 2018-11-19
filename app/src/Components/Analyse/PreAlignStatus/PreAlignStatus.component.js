import React from 'react';
import {Redirect} from 'react-router';

const PreAlignStatus = (props) => {
    if (props.progress === 100) {
        return <Redirect to={'/job/' + props.jobId + '/' + props.jobPassword + '/align'}/>;
    } else {
        return <div>
            <h3>Volume analysis... </h3>
            <div className="bar-container">
                <div className='ui progress' data-percent={props.progress}>
                    <div className='bar' style={{width: props.progress + '%'}}>
                        {/* <div className='progress'>{this.state.progress + '%'}</div> */}
                    </div>
                </div>
                <div className="percentage">{ props.progress + '%'}</div>
            </div>
        </div>
    }
};

export default PreAlignStatus;
