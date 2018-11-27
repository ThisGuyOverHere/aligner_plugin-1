import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Redirect} from "react-router";

class CompletedAnimation extends Component {
    static propTypes = {
        jobId: PropTypes.string,
        jobPassword: PropTypes.string
    };
    redirect = null;

    constructor(props) {
        super(props);
        this.state = {
            redirect: false
        }
    }

    componentDidMount() {
        this.redirect = setInterval(this.onRedirect, 2000);
    }

    componentWillUnmount() {
        clearInterval(this.redirect);
    }

    onRedirect = () => {
        this.setState({
            redirect: true
        });
    };

    render() {
        return (this.state.redirect ?
                <Redirect to={'/job/' + this.props.jobId + '/' + this.props.jobPassword + '/align'}/>
                :
                <div id={"completed-animation"}>
                    <div className="svg-container">
                        <svg className="ft-green-tick" xmlns="http://www.w3.org/2000/svg" height="100" width="100"
                             viewBox="0 0 48 48"
                             aria-hidden="true">
                            <circle className="circle" fill="#5bb543" cx="24" cy="24" r="22"/>
                            <path className="tick" fill="none" stroke="#FFF" strokeWidth="6" strokeLinecap="round"
                                  strokeLinejoin="round" strokeMiterlimit="10" d="M14 27l5.917 4.917L34 17"/>
                        </svg>
                    </div>
                    <h1> Alignment completed </h1>
                </div>
        )
    }
}

export default CompletedAnimation;
