import React, {Component} from 'react';
import { Redirect } from 'react-router';
import {httpAlignJob} from "../../../HttpRequests/Alignment.http";
import env from "../../../Constants/Env.constants";
import PropTypes from "prop-types";

class PreAlignStatus extends Component {
    static propTypes = {};

    constructor(props) {
        super(props);
        this.state = {
            job: {
                password: props.props.match.params.password,
                id: props.props.match.params.jobID
            },
            progress: 30,
        }
    };

    componentDidMount() {
      /*httpAlignJob(this.state.job.id)
          .then( response => {
              this.setState(
                  {
                      progress: 100,
                  }
              );
          })
          .catch( error => {
              console.log(error);
          })
          */
    };

    render() {
        if (this.state.progress === 100) {
            return <Redirect to={'/job/' +  this.state.job.id  + '/' + this.state.job.password + '/align'}/>;
        }else{
            return<div>
                <h3>Volume analysis... </h3>
                <div className="bar-container">
                    <div className='ui progress' data-percent={this.state.progress}>
                        <div className='bar' style={{width: this.state.progress + '%' }}>
                            { /* <div className='progress'>{this.state.progress + '%'}</div> */}
                        </div>
                    </div>
                    <div className="percentage">{this.state.progress + '%'}</div>
                </div>
            </div>
        }
    }
}

export default PreAlignStatus;