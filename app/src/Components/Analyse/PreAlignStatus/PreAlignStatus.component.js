import React, {Component} from 'react';
import { Redirect } from 'react-router';
import {httpAlignJob} from "../../../HttpRequests/Alignment.http";
import env from "../../../Constants/Env.constants";
import PropTypes from "prop-types";

class PreAlignStatus extends Component {
    static propTypes = {

    };

    constructor(props) {
        super(props);
        this.state = {
            algorithm: env.alignAlgorithmDefaultVersion,
            job: {
                password: props.pro.match.params.password,
                id: props.pro.match.params.jobID
            },
            progress: 30,
        }
    };

    componentDidMount() {
      httpAlignJob(1, this.state.algorithm)
          .then( response => {
              console.log(response);
              this.setState(
                  {
                      progress: 100,
                  }
              );
          })
          .catch( error => {
              console.log(error);
          })
    };

    render() {
        if (this.state.progress === 100) {
            console.log('here');
            return <Redirect to={'/job/' +  this.state.job.id  + '/' + this.state.job.password + '/align'}/>;
            //return <Redirect to='/job/1/bb4e2ddd4635/align'/>;
        }else{
            return <div className="bar-container">
                <div className='ui progress' data-percent={this.state.progress}>
                    <div className='bar' style={{width: this.state.progress + '%' }}>
                        <div className='progress'>{this.state.progress + '%'}</div>
                    </div>
                </div>
            </div>
        }
    }
}

export default PreAlignStatus;