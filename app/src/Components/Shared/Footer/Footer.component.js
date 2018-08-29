import React, {Component} from 'react';

class FooterComponent extends Component {
    constructor(props) {
        super(props);
        const jobID = (this.props.match
            && this.props.match.params
            && this.props.match.params.jobID) ? this.props.match.params.jobID : null;
        this.state = {
            job: {
                name: null,
                id: jobID,
                segments: null
            },
        }
    }

    static getDerivedStateFromProps(nextProps, prevState) {

        if(nextProps.match.params && nextProps.match.params.jobID){
            prevState.job.id = nextProps.match.params.jobID;
        }else{
            prevState.job.id = null;
        }

        return prevState;
    }


    renderHtml = () => {
        if(this.state.job.id){
            return <ul className="inline-list align-middle" style={{float:'left'}}>
                    <li className="list-inline__item">
                        <div className='ui progress' data-percent="50">
                            <div className='bar' style={{width:'50%'}}/>
                        </div>
                        50%
                    </li>
                    <li className="list-inline__item">
                        <p>Lines: <b>8</b></p>
                    </li>
                    <li className="list-inline__item"><p>Empty spaces Source: <b>3</b> / Target: <b>2</b></p></li>
                </ul>;
        }
    };
    render() {
        return (
            <div id="footer">
                {this.renderHtml()}
                <ul className="inline-list align-middle" style={{float:'right'}}>
                    <li className="list-inline__item">
                        <a href="https://www.matecat.com/open-source/" target="_blank">Open source</a>
                    </li>
                    <li className="list-inline__item">
                        <a target="_blank" href="/api/docs">API</a>
                    </li>
                    <li className="list-inline__item">
                        <a href="https://www.matecat.com/terms" target="_blank">Terms</a>
                    </li>
                    <li className="list-inline__item">
                        <a href="https://blog.matecat.com" target="_blank">Blog</a>
                    </li>
                </ul>
            </div>
        );
    }
}

export default FooterComponent;